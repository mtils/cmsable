<?php namespace Cmsable\Routing;

use App;
use Cmsable\Cms\Action\Action;
use Cmsable\Http\CmsPath;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\PageType\PageType;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;

use function array_key_exists;
use function preg_match_all;


class SiteTreePathFinder implements SiteTreePathFinderInterface{

    protected $currentPathProvider;

    protected $siteTreeModel;

    protected $router;

    protected $urlGenerator;

    public $routeScope = 'default';

    protected $scopeRepo = null;

    protected $treeModelManager = null;

    protected $pageTypes;

    protected $pageTypeToUriCache = [];

    protected $parameterUriCache = [];

    public function __construct(SiteTreeModelInterface $siteTreeModel,
        CurrentCmsPathProviderInterface $provider,
        Router $router,
        UrlGenerator $urlGenerator){

        $this->currentPathProvider = $provider;
        $this->siteTreeModel = $siteTreeModel;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;

    }

    public function toPage($pageOrId){

        $page = ($pageOrId instanceof SiteTreeNodeInterface) ? $pageOrId : $this->siteTreeModel->pageById($pageOrId);

        $redirectType = $page->getRedirectType();

        if($redirectType && $redirectType != SiteTreeNodeInterface::NONE){
            return $this->recalculatePagePath($page);
        }

        if($path = $page->getPath()){
            return $this->cleanHomePath($page->getPath());
        }

        return $this->recalculatePagePath($page);

    }

    public function toRoutePath($path, array $params=[], $searchMethod=self::NEAREST){

    }

    public function toRouteName($name, array $params=[], $searchMethod=self::NEAREST){

        if(!$currentRoute = $this->currentRoute()){
            return;
        }

        // I doubt this is usefull
        // if(!$currentRoute->getName()){
        //     return;
        // }

        if(!$targetRoute = $this->router->getRoutes()->getByName($name)){
            return;
        }

        $currentUri = $currentRoute->uri();
        $targetUri = $targetRoute->uri();
        $isCreateAction = false;

        // Small patch to allow indexed parameters in newer laravel versions
        // (that only support named parameters)
        if (count($params) && array_key_exists(0, $params)) {
            if ($names = $targetRoute->parameterNames()) {
                $namedParameters = [];
                foreach ($names as $i=>$paramName) {
                    if (array_key_exists($i, $params)) {
                        $namedParameters[$paramName] = $params[$i];
                    }
                }
                if (count($namedParameters)) {
                    $params = $namedParameters;
                }
            }
        }
        $hasValues = false;
        foreach ($params as $key=>$value) {
            if ($value) {
                $hasValues = true;
                break;
            }
        }
        // Emulate old behaviour of returning broken urls like in old laravel versions
        if (!$hasValues) {
            $optionalParameters = static::getOptionalParameterNames($targetUri);
            foreach ($params as $key=>$value) {
                if (!in_array($key, $optionalParameters)) {
                    $params[$key] = 'legacy';
                }
            }
        }

        // Special handling for pagetypes to create routes
        // if store is performed, the current uri is the same as the index route
        // If you route inside store to index it would then lead to the current
        // page, which points to create and not to index.
        if ($currentPageType = $this->currentPageType()) {
            $currentTargetPath = $currentPageType->getTargetPath();
            if (str_ends_with($currentTargetPath, '/create')) {
                $isCreateAction = true;
            }
        }

        // If the path starts with the same segment, try to route inside this
        // segment
        if($this->hasSameHead($currentUri, $targetUri) && $page = $this->currentPage() && !$isCreateAction)
        {
            $targetPath = $this->urlGenerator->route($name, $params, false);
            return $this->replaceWithPagePath($targetPath);
        }

        if(!$cmsPath = $this->currentCmsPath()){
            return '';
        }


        $target = $this->urlGenerator->route($name, $params, false);
        $scope = $this->scopeRepository()->get($this->routeScope);

        if ($pageType = $this->pageTypeOfUri($targetUri)) {
            if($pageTypeUri = $this->toPageType($pageType)) {
                return $pageTypeUri;
            }
        }

        if ($pagePath = $this->findForParameterRoute($targetUri, $target)) {
            return $pagePath;
        }

        return ltrim($scope->getPathPrefix() . '/' . trim($target, '/'),'/');

    }

    public function toPageType($pageType, array $params=[], $searchMethod= self::NEAREST){

        $pageTypeId = ($pageType instanceof PageType) ? $pageType->getId() : $pageType;

        if(!$pages = $this->siteTreeModel->pagesByTypeId($pageTypeId)){
            return '';
        }

        $lowestDepth = 1000;
        $topMost = NULL;
        $i=0;

        foreach($pages as $page){
            if($page->getDepth() < $lowestDepth){
                $topMost = $i;
                $lowestDepth = $page->getDepth();
            }
            $i++;
        }

        return $this->toPage($pages[$topMost]);

    }

    public function toControllerAction($action, array $params=[], $searchMethod= self::NEAREST){

        $currentRoute = $this->currentRoute();

        $currentController = $this->getControllerClass($currentRoute);

        if(strpos($action,'@')){
            list($requestedController, $requestedAction) = explode('@', $action);
        }
        else{
            $requestedController = $currentController;
            $requestedAction = $action;
        }

        // If the current controller is the requested we will try to find the
        // action inside the current route (group)
        if( ($requestedController == $currentController) && $currentRoute->getName()){

            $actionRouteName = $this->replaceAction($currentRoute->getName(), $requestedAction);

            if($actionRoute = $this->router->getRoutes()->getByName($actionRouteName)){

                $actionController = $this->getControllerClass($actionRoute);

                if($actionController == $currentController && $this->currentPage()){

                    $targetPath = $this->urlGenerator->route($actionRouteName, $params, FALSE);

                    return $this->replaceWithPagePath($targetPath);

                }

            }
        }

        if(!mb_strpos($action,'@')){
            if($cmsPath = $this->currentCmsPath()){

                if($page = $cmsPath->getMatchedNode()){

                    if($route = $this->currentRoute()){

                        if($this->hasIndexUri($route)){
                            return $this->toPage($page) . '/' . ltrim($action,'/');
                        }

                        if($path = $this->findParentControllerPath($route, $cmsPath)){
                            return $path . '/' . ltrim($action,'/');
                        }

                    }
                    return $this->toPage($page) . '/' . ltrim($action,'/');
                }
            }
        }

        return '';

    }

    public function toCmsAction(Action $action, array $params=[], $searchMethod= self::NEAREST){

    }

    /**
     * @param string $routeUri
     * @return string[]
     */
    public static function getOptionalParameterNames(string $routeUri) : array
    {
        $matches = [];
        preg_match_all('/\{(\w+?)\?\}/', $routeUri, $matches);
        return $matches[1] ?? [];
    }

    protected function findParentControllerPath($route, $cmsPath){

        $pathParts = explode('/',$cmsPath->getOriginalPath());
        $uriParts = explode('/',$route->uri());

        for($i=count($uriParts)-1,$cutted=0; $i >= 0; $i--,$cutted++){

            if(str_starts_with($uriParts[$i],'{')){
                continue;
            }

            $poppedPath = implode('/',array_slice($uriParts,0,$i+1));

            if($route = $this->findRouteByUri($poppedPath)){

                if($this->hasIndexUri($route)){
                    return implode('/',array_slice($pathParts,0,count($pathParts)-$cutted));
                }

            }
        }

    }

    protected function hasIndexUri($route){

        $cleanedUri = trim($route->uri(),'/');

        if(!str_contains($cleanedUri, '/') || $cleanedUri == ''){
            return TRUE;
        }

        $action = $this->getControllerAction($route);

        if(str_contains(strtolower($action), 'index')){
            return TRUE;
        }

        return FALSE;

    }

    protected function getControllerClass($route){

        $controllerAction = $route->getActionName();

        if(strpos($controllerAction,'@')){
            list($controller, $action) = explode('@', $controllerAction);
            return $controller;
        }

    }

    protected function getControllerAction($route){

        $controllerAction = $route->getActionName();

        if(strpos($controllerAction,'@')){
            list($controller, $action) = explode('@', $controllerAction);
            return $action;
        }

    }

    protected function recalculatePagePath(SiteTreeNodeInterface $page){

        if($page->getRedirectType() == SiteTreeNodeInterface::NONE){
            $target = $this->siteTreeModel->pageById($page->id);
            return $target ? $target->getPath() : $page->getPath();
        }

        $path = $this->calculateRedirectPath($page);
        if ($anchor = $page->getRedirectAnchor()) {
            return $path.$anchor;
        }
        return $path;

    }

    protected function calculateRedirectPath(SiteTreeNodeInterface $redirect, $filter='default'){

        if($redirect->getRedirectType() == SiteTreeNodeInterface::EXTERNAL){

            return $redirect->getRedirectTarget();

        }

        if($redirect->getRedirectType() == SiteTreeNodeInterface::INTERNAL){

            $target = $redirect->getRedirectTarget();

            if (is_numeric($target)) {
                if($targetPage = $this->siteTreeModel->pageById((int)$target)){
                    if($targetPage->getRedirectType() == SiteTreeNodeInterface::NONE){
                        return $this->cleanHomePath($targetPage->getPath());
                    }
                    return '_error_1';
                }
                elseif($targetPage = $this->siteTreeModel->newNode()->find((int)$target)){

                    if(!$treeModel = $this->modelForPage($targetPage)){
                        return '_error_2';
                    }

                    if(!$assignedPage = $treeModel->pageById((int)$target)){
                        return;
                    }
                    return $this->cleanHomePath($assignedPage->getPath());
                }
            }
            elseif($target == SiteTreeNodeInterface::FIRST_CHILD){

                if($redirect->hasChildNodes()){
                    if($child = $this->findFirstNonRedirectChild($redirect->filteredChildren($filter), $filter)){
                        return $child->getPath();
                    }

                    return '_error_3';

                }

            }
        }

        return '_error_4';

    }

    protected function findFirstNonRedirectChild($childNodes, $filter='default'){

        foreach($childNodes as $child){
            if($child->getRedirectType() == SiteTreeNodeInterface::NONE){
                return $child;
            }
            if($child->hasChildNodes()){
                return $this->findFirstNonRedirectChild($child->filteredChildren($filter));
            }
            break;
        }

    }

    protected function replaceWithPagePath($routePath){

        if($cmsPath = $this->currentCmsPath()){

            $pageTypePath = $cmsPath->getPageType()->getTargetPath();
            $page = $cmsPath->getMatchedNode();

            list($pagePath, $params) = [$this->toPage($page), ''];

            if (strpos($pagePath, '?')) {
                list($pagePath, $params) = explode('?', $pagePath, 2);
                $params = "?$params";
            }

            $pagePath = parse_url($pagePath, PHP_URL_PATH);

            if ($pageTypePath[0] != '/' && $pagePath[0] == '/') {
                $pageTypePath = "/$pageTypePath";
            }

            $url = $this->replacePathHead($pageTypePath, $pagePath, $routePath);

            return $url;

        }

        return $routePath;

    }

    public function replacePathHead($oldHead, $newHead, $path){
        return preg_replace('#'.$oldHead.'#', $newHead, $path, 1);
    }

    public function hasSameHead($path1, $path2){

        $path1 = explode('/',$path1);
        $path2 = explode('/',$path2);

        foreach($path1 as $idx=>$part){
            if(isset($path2[$idx]) && $path2[$idx] == $part){
                return TRUE;
            }
        }

        return FALSE;

    }

    protected function replaceAction($routeName, $newAction){

        $tiles = explode('.',$routeName);
        $last = array_pop($tiles);
        $tiles[] = $newAction;

        return implode('.',$tiles);
    }

    protected function currentPage(){
        if($cmsPath = $this->currentCmsPath()){
            return $cmsPath->getMatchedNode();
        }
    }

    protected function currentPageType(){
        if($cmsPath = $this->currentCmsPath()){
            return $cmsPath->getPageType();
        }
    }

    protected function currentCmsPath(){
        return $this->currentPathProvider->getCurrentCmsPath($this->routeScope);
    }

    protected function currentRoute(){
        return $this->router->current();
    }

    protected function currentRouteName(){
        if($route = $this->currentRoute()){
            return $route->getName();
        }
    }

    protected function findRouteByUri($uri){
        foreach($this->router->getRoutes() as $route){
            if($route->uri() == $uri){
                return $route;
            }
        }
    }

    protected function modelForPage($page){
        $scope = $this->scopeRepository()->getByModelRootId($page->{$page->rootIdColumn});
        return $this->treeModelManager()->get($scope);
    }

    protected function scopeRepository(){

        if(!$this->scopeRepo){
            $this->scopeRepo = App::make('Cmsable\Routing\TreeScope\RepositoryInterface');
        }
        return $this->scopeRepo;

    }

    protected function treeModelManager(){

        if(!$this->treeModelManager){
            $this->treeModelManager = App::make('Cmsable\Model\TreeModelManagerInterface');
        }

        return $this->treeModelManager;

    }

    protected function cleanHomePath($path){

        if(str_ends_with($path, CmsPath::$homeSegment)){
            return substr($path, 0, strlen($path)-strlen(CmsPath::$homeSegment));
        }
        return $path;

    }

    protected function pageTypeOfUri($uri)
    {
        if (isset($this->pageTypeToUriCache[$uri])) {
            return $this->pageTypeToUriCache[$uri];
        }

        if ($pageTypeId = $this->findPageTypeOfUri($uri)) {
            $this->pageTypeToUriCache[$uri] = $pageTypeId;
            return $pageTypeId;
        }

        return '';

    }

    protected function findPageTypeOfUri($uri)
    {
        foreach ($this->pageTypes()->all() as $pageType) {
            if ($pageType->getTargetPath() == $uri) {
                return $pageType;
            }
        }
        return '';
    }

    protected function pageTypes()
    {
        if (!$this->pageTypes) {
            $this->pageTypes = App::make('Cmsable\PageType\RepositoryInterface');
        }
        return $this->pageTypes;
    }

    /**
     * If we have a to call to /controller-endpoint/{id} we would search for
     * a page with controller-endpoint as path.
     *
     * @param $uri
     * @param $target
     * @return string|string[]|null
     */
    protected function findForParameterRoute($uri, $target)
    {
        if (isset($this->parameterUriCache[$uri])) {
            $prefix = $this->parameterUriCache[$uri]['parameterLess'];
            $pagePath = $this->parameterUriCache[$uri]['pagePath'];
            return $prefix ? $this->replacePathHead($prefix, $pagePath, $target) : '';
        }

        $this->parameterUriCache[$uri] = [
            'parameterLess' => '',
            'pagePath'      => ''
        ];

        // If the route has no parameters we cannot continue
        if (!$parameterLess = $this->uriUntilParametersIfFound($uri)) {
            return '';
        }

        // If no page type was found with the parameterless start we give up
        if (!$pageType = $this->pageTypeOfUri($parameterLess)) {
            return '';
        }

        // If no page with PageType was found we also give up
        if(!$pagePath = $this->toPageType($pageType)) {
            return '';
        }

        $this->parameterUriCache[$uri] = [
            'parameterLess' => $parameterLess,
            'pagePath'      => $pagePath
        ];

        return $this->replacePathHead($parameterLess, $pagePath, $target);
    }

    /**
     * Return the uri until the first parameter was found. If no parameters were
     * found it returns an empty string.
     *
     * @param string $uri
     *
     * @return string
     */
    protected function uriUntilParametersIfFound($uri)
    {
        $segmentsBeforeParameters = [];
        $uriSegments = explode('/', $uri);
        $parametersFound = '';

        foreach ($uriSegments as $segment) {
            $part = trim($segment);

            if (substr($segment, 0, 1) === '{' && substr($part, -1) === '}') {
                $parametersFound = true;
                break;
            }
            $segmentsBeforeParameters[] = $part;
        }

        return $parametersFound ? implode('/', $segmentsBeforeParameters) : '';
    }
}

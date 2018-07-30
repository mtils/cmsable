<?php namespace Cmsable\Html;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\Router;

use Cmsable\PageType\RepositoryInterface as PageTypes;
use Cmsable\Model\TreeModelManagerInterface;
use Cmsable\Routing\SiteTreePathFinderInterface;
use Cmsable\Routing\SiteTreePathFinder;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Routing\TreeScope\RepositoryInterface as TreeScopeRepository;
use Cmsable\Routing\TreeScope\TreeScope;
use Cmsable\Http\CurrentCmsPathProviderInterface;

class SiteTreeUrlGenerator extends UrlGenerator{

    protected $pathFinder;

    protected $treeModelManager;

    protected $treeScope;

    protected $treeScopeRepository;

    protected $originalUrlGenerator;

    protected $currentCmsPathProvider;

    protected $router;

    /**
     * @var Cmsable\PageType\RepositoryInterface
     **/
    protected $pageTypes;

    protected static $generators = [];

    protected static $pathFinders = [];

    /**
     * Generate a absolute URL to the given path.
     *
     * @param  mixed  $path or SiteTreeNodeInterface Instance
     * @param  mixed  $extra
     * @param  bool  $secure
     * @return string
     */
    public function to($path, $extra = array(), $secure = null)
    {

        // Page object passed
        if(is_object($path) && $path instanceof SiteTreeNodeInterface){
            if(starts_with($path->getPath(),'http:')){
                $path = $path->getPath();
            }
            else{
                $path = $this->getPathFinder()->toPage($path);
            }
        }

        // PageTypeId passed
        elseif (is_string($path) && $this->pageTypes->has($path)) {

            $path = $this->getPathFinder()->toPageType($path);

        }

        // Path passed inside CMS-SiteTree
        elseif (is_string($path) && $this->currentCmsPathProvider->
                                        getCurrentCmsPath()
                                            ->isCmsPath()) {

            if ($extra && !isset($extra[0])) {
                $extra = array_values($extra);
                $extraPath = implode('/',$extra);
                $path = trim($path,'/') . '/' . trim($extraPath,'/');
                $extra = [];
            }

            if ($path == '/' && $this->getTreeScope()) {
                $path = trim($this->getTreeScope()->getPathPrefix() . $path, '/');
            }
        }

        elseif ($path == '/' && $treeScope = $this->getTreeScope()) {
            $path = trim($treeScope->getPathPrefix() . $path, '/');
        }

        return parent::to($path, $extra, $secure);
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = array(), $absolute = true, $route = null)
    {
        if($path = $this->getPathFinder()->toRouteName($name, $parameters)){
            if($absolute){
                return $this->to($path);
            }
            return $path;
        }

        return parent::route($name, $parameters, $absolute, $route);
    }

    /**
    * Get the URL to a controller action.
    *
    * @param  string  $action
    * @param  mixed   $parameters
    * @param  bool    $absolute
    * @return string
    */
    public function action($action, $parameters = array(), $absolute = true)
    {

        if($path = $this->getPathFinder()->toControllerAction($action, $parameters)){

            if(!$absolute){
                return $path;
            }

            return $this->to($path);
        }

        return parent::action($action, $parameters, $absolute);

    }

    public function page($page=NULL, $extra = array(), $secure = null){

        if($page === NULL){
            $page = $this->currentCmsPathProvider->getCmsPath()->getMatchedNode();
        }

        if($page instanceof SiteTreeNodeInterface){
            return $this->to($page, $extra, $secure);
        }

    }

    public function currentPage($extra=[], $secure = null){

        return $this->to($this->currentCmsPathProvider->getCmsPath()->getMatchedNode(), $extra, $secure);

    }

    public function getPathFinder(){
        if(!$this->pathFinder){
            $this->pathFinder = $this->makePathFinder($this->getTreeScope());
        }
        return $this->pathFinder;
    }

    public function setPathFinder(SiteTreePathFinderInterface $pathFinder){
        $this->pathFinder = $pathFinder;
        return $this;
    }

    public function getTreeModelManager(){
        return $this->treeModelManager;
    }

    public function setTreeModelManager(TreeModelManagerInterface $manager){
        $this->treeModelManager = $manager;
        return $this;
    }

    public function getTreeScopeRepository(){
        return $this->treeScopeRepository;
    }

    public function setTreeScopeRepository(TreeScopeRepository $treeScopeRepo){
        $this->treeScopeRepository = $treeScopeRepo;
        return $this;
    }

    public function getOriginalUrlGenerator(){
        return $this->originalUrlGenerator;
    }

    public function setOriginalUrlGenerator(UrlGenerator $generator){
        $this->originalUrlGenerator = $generator;
        return $this;
    }

    public function getCurrentCmsPathProvider(){
        return $this->currentCmsPathProvider;
    }

    public function setCurrentCmsPathProvider(CurrentCmsPathProviderInterface $provider){
        $this->currentCmsPathProvider = $provider;
        return $this;
    }

    public function getRouter(){
        return $this->router;
    }

    public function setRouter(Router $router){
        $this->router = $router;
        return $this;
    }

    public function getTreeScope(){

        if(!$this->treeScope){
            $this->treeScope = $this->currentCmsPathProvider->getCurrentCmsPath()->getTreeScope();
        }

        return $this->treeScope;

    }

    public function setTreeScope(TreeScope $treeScope){
        $this->treeScope = $treeScope;
        return $this;
    }

    public function scope($scope){
        $scopeName = ($scope instanceof TreeScope) ? $scope->getName() : $scope;
        return $this->getSubGenerator($scopeName);
    }

    public function getPageTypes()
    {
        return $this->pageTypes;
    }

    public function setPageTypes(PageTypes $repository)
    {
        $this->pageTypes = $repository;
        return $this;
    }

    protected function getSubGenerator($scopeName){

        if(isset(static::$generators[$scopeName])){
            return static::$generators[$scopeName];
        }

        $generator = $this->copy();
        $generator->setTreeScope($this->treeScopeRepository->get($scopeName));

        static::$generators[$scopeName] = $generator;

        return $generator;

    }

    protected function copy(){

        $generator = new static($this->routes, $this->request);
        $generator->setTreeModelManager($this->treeModelManager);
        $generator->setTreeScopeRepository($this->treeScopeRepository);
        $generator->setOriginalUrlGenerator($this->originalUrlGenerator);
        $generator->setCurrentCmsPathProvider($this->currentCmsPathProvider);
        $generator->setPageTypes($this->pageTypes);
        $generator->setRouter($this->router);

        if($this->forceScheme){
            $generator->forceScheme($this->forceScheme);
        }

        return $generator;

    }

    protected function makePathFinder(TreeScope $treeScope){

        $finder = new SiteTreePathFinder(
            $this->treeModelManager->get($treeScope),
            $this->getCurrentCmsPathProvider(),
            $this->router,
            $this->getOriginalUrlGenerator()
        );

        $finder->routeScope = $treeScope->getName();

        return $finder;

    }

}

<?php namespace Cmsable\Routing;

use Illuminate\Routing\Router;

use Cmsable\Cms\Action\Action;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Cms\PageType;
use Cmsable\Http\CmsPath;

class SiteTreePathFinder implements SiteTreePathFinderInterface{

    protected $currentPathProvider;

    protected $siteTreeModel;

    protected $router;

    public $routeScope = 'default';

    public function __construct(SiteTreeModelInterface $siteTreeModel,
                                CurrentCmsPathProviderInterface $provider,
                                Router $router){

        $this->currentPathProvider = $provider;
        $this->siteTreeModel = $siteTreeModel;
        $this->router = $router;

    }

    public function toPage($pageOrId){

        $page = ($pageOrId instanceof SiteTreeNodeInterface) ? $pageOrId : $this->siteTreeModel->pageById($pageOrId);

        if($page->getRedirectType() == SiteTreeNodeInterface::NONE){
            if($path = $page->getPath()){
                if(ends_with($path, CmsPath::$homeSegment)){
                    return substr($path, 0, strlen($path)-strlen(CmsPath::$homeSegment));
                }
                return $path;
            }
        }

        return $this->recalculatePagePath($page);

    }

    public function toRoutePath($path, $searchMethod=self::NEAREST){
    
    }

    public function toRouteName($name, $searchMethod=self::NEAREST){
    
    }

    public function toPageType($pageType, $searchMethod= self::NEAREST){

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

    public function toControllerAction($action){

        if(!mb_strpos($action,'@')){
            if($cmsPath = $this->currentPathProvider->getCurrentCmsPath($this->routeScope)){

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

    public function toCmsAction(Action $action){
    
    }

    protected function findParentControllerPath($route, $cmsPath){

        $pathParts = explode('/',$cmsPath->getOriginalPath());
        $uriParts = explode('/',$route->uri());

        for($i=count($uriParts)-1,$cutted=0; $i >= 0; $i--,$cutted++){

            if(starts_with($uriParts[$i],'{')){
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

        if(strpos($cleanedUri, '/') === FALSE || $cleanedUri == ''){
            return TRUE;
        }

        $action = $this->getControllerAction($route);

        if(strpos(strtolower($action),'index') !== FALSE){
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

    protected function currentPageTypeControllerClass(){

        if($route = $this->currentRoute()){
            $currentControllerAction = $route->getActionName();
            if(strpos($currentControllerAction,'@')){
                list($controller, $currentAction) = explode('@',$currentControllerAction);
                return $controller;
            }
        }
        return $this->toPage($page) . '/' . ltrim($action,'/');
    }

    protected function recalculatePagePath(SiteTreeNodeInterface $page){

        if($page->getRedirectType() == SiteTreeNodeInterface::NONE){
            return $this->siteTreeModel->pageById($page->id)->getPath();
        }

        return $this->calculateRedirectPath($page);

    }

    protected function calculateRedirectPath(SiteTreeNodeInterface $redirect, $filter='default'){

        if($redirect->getRedirectType() == SiteTreeNodeInterface::EXTERNAL){

            return $redirect->getRedirectTarget();

        }

        if($redirect->getRedirectType() == SiteTreeNodeInterface::INTERNAL){

            $target = $redirect->getRedirectTarget();

            if(is_numeric($target)){
                if($targetPage = $this->siteTreeModel->pageById((int)$target)){
                    if($targetPage->getRedirectType() == SiteTreeNodeInterface::NONE){
                        return $targetPage->getPath();
                    }
                    else{
                        return '_error_';
                    }
                }
            }
            elseif($target == SiteTreeNodeInterface::FIRST_CHILD){

                if($redirect->hasChildNodes()){
                    if($child = $this->findFirstNonRedirectChild($redirect->filteredChildren($filter), $filter)){
                        return $child->getPath();
                    }
                    else{
                        return '_error_';
                    }
                }

            }
        }
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

}
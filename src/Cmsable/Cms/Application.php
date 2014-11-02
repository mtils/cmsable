<?php namespace Cmsable\Cms;

use InvalidArgumentException;
use OutOfBoundsException;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Input;

use Cmsable\Http\CmsPathCreatorInterface;
use Cmsable\Http\CmsRequest;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Support\EventSenderTrait;
use Cmsable\Routing\ControllerDispatcher;

class Application implements CurrentCmsPathProviderInterface{

    use EventSenderTrait;

    protected $pathCreatorLoadEventName = 'cmsable::path-creators-requested';

    protected $app;

    protected $pageTypes;

    protected $pathCreators = [];

    protected $pathSetter;

    protected $pathsSetted = FALSE;

    protected $cmsRequest;

    protected $controllerDispatcher;

    protected $scopeFilters = [];

    public function __construct(PageTypeRepositoryInterface $pageTypes, $eventDispatcher)
    {
        $this->pageTypes = $pageTypes;
        $this->setEventDispatcher($eventDispatcher);

        $this->eventDispatcher->listen('router.matched', function($route, $request){
            $this->onRouterMatch($route, $request);
        });
    }

    public function pageTypes(){
        return $this->pageTypes;
    }

    public function onRouterBefore($route, $request){

        if($cmsPath = $this->getCmsPath()){
            $scope = $this->pathPrefixToRouteScope($cmsPath->getCmsPathPrefix());
            $page = $cmsPath->getMatchedNode();
        }
        else{
            $scope = '';
            $page = NULL;
        }

        return $this->callScopeFilters($scope, $route, $request, $page);

    }

    protected function onRouterMatch($route, $request){

        if(count($this->scopeFilters)){
            $route->before('cmsable.scope-filter');
        }

    }

    public function attachCmsPath(CmsRequest $request){

        $matchedPath = null;

        foreach($this->getPathCreators() as $pathParser){

            $cmsPath = $pathParser->createFromRequest($request);

            if($cmsPath->isCmsPath()){
                $request->setCmsPath($cmsPath);
                $this->fireEvent('cmsable::cms-path-setted',[$cmsPath]);
                $this->cmsRequest = $request;
                $matchedPath = $cmsPath;
            }

        }

        if($this->controllerDispatcher){

            if($matchedPath){
                if($node = $matchedPath->getMatchedNode()){
                    $this->controllerDispatcher->setPage($node);
                }

                if($pageType = $matchedPath->getPageType()){

                    if($creator = $pageType->getControllerCreator()){
                        $this->controllerDispatcher->setCreator($creator);
                    }
                    else{
                        $this->controllerDispatcher->resetCreator();
                    }
                }
                else{
                    $this->controllerDispatcher->resetCreator();
                }
            }
            else{
                $this->controllerDispatcher->resetCreator();
                $this->controllerDispatcher->resetPage();
            }
        }

    }

    public function getPathCreators(){

        if(!$this->pathCreators){
            $this->fireEvent($this->pathCreatorLoadEventName,[$this], $once=TRUE);
        }

        return $this->pathCreators;
    }

    public function setPathCreators(array $pathCreators){

        $this->pathCreators = [];

        foreach($pathCreators as $creator){
            $this->addPathCreator($creator);
        }

        return $this;

    }

    public function addPathCreator(CmsPathCreatorInterface $creator){
        $this->pathCreators[] = $creator;
        return $this;
    }

    public function removePathCreator(CmsPathCreatorInterface $creator){

        $i = 0;
        $kill = -1;

        foreach($this->pathCreators as $c){
            if($c === $creator){
                $kill = $i;
                break;
            }
            $i++;
        }

        if($kill != -1){
            unset($this->pathCreators[$kill]);
            $this->pathCreators = array_values($this->pathCreators);
            return $this;
        }

        throw new OutOfBoundsException('Cannot remove unadded CmsPathCreator');
    }

    public function getCmsPath($path=NULL){

        if($path === NULL){
            if($this->cmsRequest instanceof CmsRequest &&
                $cmsPath = $this->cmsRequest->getCmsPath()){
                return $cmsPath;
            }

            return;
        }

        foreach($this->getPathCreators() as $creator){
            if($cmsPath = $creator->createFromPath($path)){
                return $cmsPath;
            }
        }

    }

    public function getCurrentCmsPath($routeScope='default'){

        $cmsPathPrefix = $this->routeScopeToPathPrefix($routeScope);

        if($cmsPath = $this->getCmsPath()){
//             echo("$cmsPath $routeScope $cmsPathPrefix");
            if($cmsPath->getCmsPathPrefix() == $cmsPathPrefix){
                return $cmsPath;
            }
        }

        if($creator = $this->getPathCreator($routeScope)){
            return $creator->createDeactivated('/');
        }

    }

    public function inSiteTree()
    {

        if($cmsPath = $this->getCmsPath()){
            return $cmsPath->isCmsPath();
        }

        return FALSE;

    }

    public function getMatchedNode(){

        if($cmsPath = $this->getCmsPath()){
            return $cmsPath->getMatchedNode();
        }

    }

    /**
     * @brief Returns the current tree scope
     *
     **/
    public function currentScope(){

        if($cmsPath = $this->getCmsPath()){
            if($cmsPath->isCmsPath()){
                return $this->pathPrefixToRouteScope($cmsPath->getCmsPathPrefix());
            }
        }

    }

    public function getPathCreator($routeScope){

        $pathPrefix = $this->routeScopeToPathPrefix($routeScope);

        foreach($this->getPathCreators() as $creator){

            if($creator->getCmsPathPrefix() == $pathPrefix){
                return $creator;
            }
        }

    }

    public function getFallbackPage(){

        if(func_num_args() == 0){
            $routeScope = $this->currentScope();
        }
        else{
            $routeScope = func_get_arg(0);
        }
        return $this->getPathCreator($routeScope)->getFallbackNode();
    }

    public function getControllerDispatcher(){
        return $this->controllerDispatcher;
    }

    public function setControllerDispatcher(ControllerDispatcher $dispatcher){
        $this->controllerDispatcher = $dispatcher;
        return $this;
    }

    public function pathPrefixToRouteScope($pathPrefix){

        if(trim($pathPrefix,'/ ') == ''){
            return 'default';
        }
        return trim($pathPrefix,'/');
    }

    public function routeScopeToPathPrefix($routeScope){

        if($routeScope == 'default' || !$routeScope){
            return '/';
        }

        return $routeScope;
    }

    public function whenScope($scope, $callable){

        if(!is_callable($callable)){
            throw new InvalidArgumentException("Scopefilter has to be callable");
        }

        if(!isset($this->scopeFilters[$scope])){
            $this->scopeFilters[$scope] = [];
        }

        $this->scopeFilters[$scope][] = $callable;

        return $this;
    }

    protected function callScopeFilters($scope, $route, $request, $page){

        foreach($this->scopeFilters as $scopePattern=>$filters){
            if(fnmatch($scopePattern, $scope)){
                foreach($filters as $filter){
                    if($response = $filter($scope, $route, $request, $page)){
                        return $response;
                    }
                }
            }
        }

    }
}
<?php namespace Cmsable\Cms;

use InvalidArgumentException;
use OutOfBoundsException;

use Input;
use App;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Cmsable\Http\CmsPathCreatorInterface;
use Cmsable\Http\CmsRequest;
use Cmsable\Http\CmsPath;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Support\EventSenderTrait;
use Cmsable\Routing\ControllerDispatcher;
use Cmsable\Routing\CurrentScopeProviderInterface;
use Cmsable\PageType\RepositoryInterface as PageTypeRepository;
use Cmsable\Routing\TreeScope\DetectorInterface;

class Application implements CurrentCmsPathProviderInterface, CurrentScopeProviderInterface{

    use EventSenderTrait;

    protected $pathCreatorLoadEventName = 'cmsable::path-creators-requested';

    protected $pathSettedEventName = 'cmsable::cms-path-setted';

    protected $scopeChangedEventName = 'cmsable::treescope-changed';

    protected $app;

    protected $pathCreator;

    protected $cmsRequest;

    protected $controllerDispatcher;

    protected $scopeFilters = [];

    public function __construct(CmsPathCreatorInterface $pathCreator,
                                $eventDispatcher){


        $this->pathCreator = $pathCreator;
        $this->setEventDispatcher($eventDispatcher);

        $this->eventDispatcher->listen('router.matched', function($route, $request){
            $this->onRouterMatch($route, $request);
        });

    }

    public function onRouterBefore($route, $request){
        return $this->callScopeFilters($route, $request);

    }

    protected function onRouterMatch($route, $request){

        if(count($this->scopeFilters)){
            $route->before('cmsable.scope-filter');
        }

    }

    public function attachCmsPath(CmsRequest $request){

        $cmsPath = $this->pathCreator->createFromRequest($request);

        $this->cmsRequest = $request;

        $request->setCmsPath($cmsPath);

        if($cmsPath->isCmsPath()){
            $this->fireEvent($this->pathSettedEventName,[$cmsPath]);
        }

        $this->fireEvent($this->scopeChangedEventName, [$cmsPath->getTreeScope()]);

        if($this->controllerDispatcher){
            $this->configureControllerDispatcher($this->controllerDispatcher, $cmsPath);
        }

    }

    public function configureControllerDispatcher(ControllerDispatcher $dispatcher,
                                                  CmsPath $cmsPath){

        if(!$cmsPath->isCmsPath()){
            $dispatcher->resetCreator();
            $dispatcher->resetPage();
            return;
        }

        if($node = $cmsPath->getMatchedNode()){
            $dispatcher->setPage($node);
        }

        if(!$pageType = $cmsPath->getPageType()){
            $dispatcher->resetCreator();
            return;
        }

        if($creatorClass = $pageType->getControllerCreatorClass()){
            $dispatcher->setCreator(App::make($creatorClass));
            return;
        }

        $dispatcher->resetCreator();

    }

    // $path nie übergeben!
    public function getCmsPath(){

        if(!$this->cmsRequest instanceof CmsRequest){
            return;
        }

        if($cmsPath = $this->cmsRequest->getCmsPath()){
            return $cmsPath;
        }

    }

    public function getCurrentCmsPath(){

        return $this->getCmsPath();

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

        if(!$cmsPath = $this->getCmsPath()){
            return;
        }

        return $cmsPath->getTreeScope();

    }

    public function getControllerDispatcher(){
        return $this->controllerDispatcher;
    }

    public function setControllerDispatcher(ControllerDispatcher $dispatcher){
        $this->controllerDispatcher = $dispatcher;
        return $this;
    }

    public function whenScope($scopeName, $callable){

        if(!is_callable($callable)){
            throw new InvalidArgumentException("Scopefilter has to be callable");
        }

        if(!isset($this->scopeFilters[$scopeName])){
            $this->scopeFilters[$scopeName] = [];
        }

        $this->scopeFilters[$scopeName][] = $callable;

        return $this;
    }

    protected function callScopeFilters($route, $request){

        $scope = $request->getCmsPath()->getTreeScope();
        $scopeName = $scope->getName();

        foreach($this->scopeFilters as $scopePattern=>$filters){
            if(fnmatch($scopePattern, $scopeName)){
                foreach($filters as $filter){
                    if($response = $filter($scope, $route, $request, $request->getCmsPath()->getMatchedNode())){
                        return $response;
                    }
                }
            }
        }

    }
}
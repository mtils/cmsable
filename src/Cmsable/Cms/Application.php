<?php namespace Cmsable\Cms;

use InvalidArgumentException;

use App;

use Symfony\Component\HttpFoundation\Request;

use Cmsable\Http\CmsPathCreatorInterface;
use Cmsable\Http\CmsRequest;
use Cmsable\Http\CmsRequestConverter;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Support\EventSenderTrait;
use Cmsable\Routing\CurrentScopeProviderInterface;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Foundation\Application as LaravelApp;

class Application implements CurrentCmsPathProviderInterface, CurrentScopeProviderInterface
{

    use EventSenderTrait;

    protected $app;

    protected $pathCreator;

    protected $cmsRequest;

    protected $requestConverter;

    protected $scopeFilters = [];

    public function __construct(CmsPathCreatorInterface $pathCreator,
                                CmsRequestConverter $requestConverter,
                                $eventDispatcher){


        $this->pathCreator = $pathCreator;
        $this->requestConverter = $requestConverter;
        $this->setEventDispatcher($eventDispatcher);

        $this->eventDispatcher->listen('cmsable::request-replaced', function($request){
            $this->setCmsRequest($request);
        });

        $this->eventDispatcher->listen(RouteMatched::class, function(RouteMatched $event){
            $this->registerScopeFilters($event->route, $event->request);
        });

    }

    public function onRouterBefore($route, $request){
        $this->callScopeFilters($route, $request);
    }

    protected function registerScopeFilters($route, $request){

        if (count($this->scopeFilters)) {
            $this->onRouterBefore($route, $request);
        }

    }

    // $path nie übergeben!
    public function getCmsPath(){
        return $this->getCmsRequest()->getCmsPath();
    }

    public function getCurrentCmsPath(){

        return $this->getCmsPath();

    }

    public function inSiteTree()
    {

        if($cmsPath = $this->getCmsPath()){
            return $cmsPath->isCmsPath();
        }

        return false;

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

    public function getCmsRequest(){

        if(!$this->cmsRequest){
            $this->cmsRequest = $this->createCmsRequest();
        }

        return $this->cmsRequest;

    }

    public function setCmsRequest(CmsRequest $cmsRequest)
    {
        $this->cmsRequest = $cmsRequest;
        return $this;
    }

    protected function attachCmsPath(CmsRequest $request){

        $cmsPath = $this->pathCreator->createFromRequest($request);
        $this->cmsRequest = $request;
        $request->setCmsPath($cmsPath);

    }

    protected function createCmsRequest(Request $request=null)
    {

        $request = ($request === NULL) ? LaravelApp::getInstance()->make('request') : $request;
        $cmsRequest = $this->requestConverter->toCmsRequest($request);

        $cmsRequest->provideCmsPath(function($cmsRequest){
            $this->attachCmsPath($cmsRequest);
        });

        return $cmsRequest;
    }
}

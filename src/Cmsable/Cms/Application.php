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

    protected $scopeFilters = [];

    public function __construct(CmsPathCreatorInterface $pathCreator,
                                $eventDispatcher){


        $this->pathCreator = $pathCreator;
        $this->setEventDispatcher($eventDispatcher);

        $this->eventDispatcher->listen('router.matched', function($route, $request){
            $this->registerScopeFilters($route, $request);
        });

    }

    public function onRouterBefore($route, $request){
        return $this->callScopeFilters($route, $request);

    }

    protected function registerScopeFilters($route, $request){

        if(count($this->scopeFilters)){
            $route->before('cmsable.scope-filter');
        }

    }

    public function attachCmsPath(CmsRequest $request){

        $cmsPath = $this->pathCreator->createFromRequest($request);

        $this->cmsRequest = $request;

        $request->setCmsPath($cmsPath);

        if($request->originalPath() != $request->path()){
            $this->fireEvent($this->pathSettedEventName,[$cmsPath]);
        }

        $this->fireEvent($this->scopeChangedEventName, [$cmsPath->getTreeScope()]);

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

    public function _updateCmsRequest(Request $request){
        $this->cmsRequest = $this->createCmsRequest($request);
        return $this->cmsRequest;
    }

    public function getCmsRequest(){

        if(!$this->cmsRequest){
            $this->cmsRequest = $this->createCmsRequest();
        }

        return $this->cmsRequest;

    }

    public function createCmsRequest(Request $request=null){

        $request = ($request === NULL) ? App::make('request') : $request;

        $cmsRequest = (new CmsRequest)->duplicate(

            $request->query->all(), $request->request->all(), $request->attributes->all(),

            $request->cookies->all(), $request->files->all(), $request->server->all()
        );

        $cmsRequest->setEventDispatcher($this->eventDispatcher);

        return $cmsRequest;

    }
}
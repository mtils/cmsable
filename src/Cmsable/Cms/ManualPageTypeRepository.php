<?php namespace Cmsable\Cms;

use DomainException;
use OutOfBoundsException;
use Illuminate\Container\Container;

class ManualPageTypeRepository implements PageTypeRepositoryInterface{

    public $loadEventName = 'cmsable.pageTypeLoadRequested';

    protected $pageTypes = array();

    protected $pageTypesLoaded = FALSE;

    protected $eventDispatcher;

    protected $eventFired = FALSE;

    protected $prototype;

    protected $app;

    protected $currentPageConfig;

    public function __construct(PageType $prototype, Container $container, $eventDispatcher=NULL){

        $this->prototype = $prototype;
        $this->app = $container;

        if($eventDispatcher){
            $this->setEventDispatcher($eventDispatcher);
        }
    }

    public function setEventDispatcher($dispatcher){
        if(!method_exists($dispatcher,'fire')){
            throw new DomainException('EventDispatcher has to have a fire method');
        }
        $this->eventDispatcher = $dispatcher;
        return $this;
    }

    public function get($id){

        $this->fireLoadEvent();

        if(isset($this->pageTypes[$id])){
            return $this->pageTypes[$id];
        }
        throw new OutOfBoundsException("No PageType found with id '$id'");
    }

    public function has($id){
        try{
            $type = $this->get($id);
            return TRUE;
        }
        catch(OutOfBoundsException $e){
            return FALSE;
        }
    }

    public function add(PageType $info){
        $this->pageTypes[$info->getId()] = $info;
        return $this;
    }

    public function fillByArray(array $pageTypes){

        foreach($pageTypes as $typeData){
            $pageType = $this->createFromArray($typeData);
            $this->add($pageType);
        }

    }

    public function createFromArray(array $pageTypeData){

        $pageType = clone $this->prototype;

        if(isset($pageTypeData['class'])){
            $pageType = $this->app->make($pageTypeData['class']);
        }
        else{
            $pageType = clone $this->prototype;
        }

        if(isset($pageTypeData['id'])){
            $pageType->setId($pageTypeData['id']);
        }
        else{
            throw new OutOfBoundsException('A PageType needs an id');
        }

        if(isset($pageTypeData['controller'])){
            $pageType->setControllerClassName($pageTypeData['controller']);
        }

        foreach(['singularName','pluralName','description','category',
                 'formPluginClass','routeScope','targetPath'] as $key){

            if(isset($pageTypeData[$key])){
                $method = 'set'.ucfirst($key);
                $pageType->$method($pageTypeData[$key]);
            }

        }

        return $pageType;
    }

    public function all($routeScope='default'){

        $this->fireLoadEvent();

        $pageTypes = [];

        foreach($this->pageTypes as $id=>$pageType){
            if( $pageType->getRouteScope() == $routeScope || !$pageType->getRouteScope()){
                $pageTypes[] = $pageType;
            }
        }
        return $pageTypes;
    }

    public function byCategory($routeScope='default'){
        $categorized = array();
        foreach($this->all($routeScope) as $info){
            if(!isset($categorized[$info->category()])){
                $categorized[$info->category()] = array();
            }
            $categorized[$info->category()][] = $info;
        }
        return $categorized;
    }

    public function getCategory($name){
        return new PageTypeCategory($name);
    }

    public function getCategories($routeScope='default'){
        $categoryNames = array_keys($this->byCategory($routeScope));
        $categories = array();
        foreach($categoryNames as $name){
            $categories[] = $this->getCategory($name);
        }
        return $categories;
    }

    protected function fireLoadEvent(){

        if($this->eventDispatcher && !$this->eventFired){
            $this->eventDispatcher->fire($this->loadEventName, $this);
            $this->eventFired = TRUE;
        }

    }

    public function currentPageConfig(){
        return $this->currentPageConfig;
    }

    public function setCurrentPageConfig($pageConfig){
        $this->currentPageConfig = $pageConfig;
        return $this;
    }

    public function resetCurrentPageConfig(){
        $this->currentPageConfig = NULL;
        return $this;
    }
}
<?php namespace Cmsable\Cms;

use DomainException;
use OutOfBoundsException;
use Illuminate\Container\Container;

class ManualPageTypeRepository implements PageTypeRepositoryInterface{

    protected $pageTypes = array();

    protected $pageTypesLoaded = FALSE;

    protected $eventDispatcher;

    protected $prototype;

    protected $app;

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
        else{
            throw new OutOfBoundsException('A PageType needs an controller classname');
        }

        foreach(['singularName','pluralName','description','category',
                 'formPluginClass','routeScope'] as $key){

            if(isset($pageTypeData[$key])){
                $method = 'set'.ucfirst($key);
                $pageType->$method($pageTypeData[$key]);
            }

        }

        if(isset($pageTypeData['subRoutables'])){
            foreach($pageTypeData['subRoutables'] as $path=>$routable){
                $pageType->addSubRoutable($path, $routable);
            }
        }

        return $pageType;
    }

    public function all($routeScope='default'){
        if(!$this->pageTypesLoaded && $this->eventDispatcher){
            $this->eventDispatcher->fire('cmsable.controllerDescriptorLoad',
                                         array($this));
            $this->pageTypesLoaded = TRUE;
        }
        $pageTypes = array();
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
}
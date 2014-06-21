<?php namespace Cmsable\Cms;

use \DomainException;
use \OutOfBoundsException;

class ControllerDescriptorLoaderManual implements ControllerDescriptorLoaderInterface{

    protected $descriptors = array();

    protected $descriptorsLoaded = FALSE;

    protected $eventDispatcher;

    public function __construct($eventDispatcher=NULL){
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
        if(isset($this->descriptors[$id])){
            return $this->descriptors[$id];
        }
        throw new OutOfBoundsException("No PageType found with id '$id'");
    }

    public function add(ControllerDescriptor $info){
        $this->descriptors[$info->getId()] = $info;
        return $this;
    }

    public function setDescriptors(array $descriptors){
        foreach($descriptors as $descriptor){
            $this->add($descriptor);
        }
    }

    public function all($routeScope='default'){
        if(!$this->descriptorsLoaded && $this->eventDispatcher){
            $this->eventDispatcher->fire('cmsable.controllerDescriptorLoad',
                                         array($this));
            $this->descriptorsLoaded = TRUE;
        }
        $descriptors = array();
        foreach($this->descriptors as $id=>$descriptor){
            if( $descriptor->getRouteScope() == $routeScope || !$descriptor->getRouteScope()){
                $descriptors[] = $descriptor;
            }
        }
        return $descriptors;
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
        return new ControllerDescriptorCategory($name);
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
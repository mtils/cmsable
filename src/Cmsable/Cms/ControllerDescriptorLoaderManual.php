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

    public function all(){
        if(!$this->descriptorsLoaded && $this->eventDispatcher){
            $this->eventDispatcher->fire('cmsable.controllerDescriptorLoad',
                                         array($this));
            $this->descriptorsLoaded = TRUE;
        }
        return $this->descriptors;
    }

    public function byCategory(){
        $categorized = array();
        foreach($this->all() as $info){
            if(!isset($categorized[$info->category()])){
                $categorized[$info->category()] = array();
            }
            $categorized[$info->category()][] = $info;
        }
        return $categorized;
    }
}
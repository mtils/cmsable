<?php namespace Cmsable\Html;

use ArrayIterator;
use OutOfBoundsException;

class MenuFilterRegistry{

    protected $filters = array();

    protected $dispatcher;

    public function __construct($eventDispatcher){
        $this->dispatcher = $eventDispatcher;
    }

    public function filteredChildren($childNodes, $filterName='default'){
        
        $filter = $this->getFilter($filterName);
        
        $newArray = array();
        foreach($childNodes as $node){
            if($filter->isVisible($node)){
                $newArray[] = $node;
            }
        }
        return $newArray;
        return new ArrayIterator($newArray);
    }

    public function getFilter($name){
        if(!isset($this->filters[$name])){
            $this->filters[$name] = new MenuFilter();
            $this->dispatcher->fire("cmsable::menu-filter.create.$name", array($this->filters[$name]));
        }
        return $this->filters[$name];
    }

    public function setFilter($name, MenuFilter $filter){
        $this->filters[$name] = $filter;
        return $this;
    }

}
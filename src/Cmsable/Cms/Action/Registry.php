<?php namespace Cmsable\Cms\Action;

use UnexpectedValueException;

class Registry{

    protected $creators = array();

    public function __construct(){
        
    }

    public function getCreators(){
        return $this->creators;
    }

    public function add($creator){

        if(!is_callable($creator)){
            throw new UnexpectedValueException('Creator has to be callable');
        }

        $this->creators[] = $creator;
        return $this;

    }

    public function get($user, $resource, $context='default'){

        $actionGroup = new Group();
        foreach($this->creators as $creator){
            $creator($actionGroup, $user, $resource, $context);
        }
        return $actionGroup;

    }
}
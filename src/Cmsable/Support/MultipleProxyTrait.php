<?php namespace Cmsable\Support;

use InvalidArgumentException;
use OutOfBoundsException;

trait MultipleProxyTrait{

    protected $targets = [];

    public function addTarget($target){

        if(!is_object($target)){
            throw new InvalidArgumentException('$target has to be an object');
        }

        $this->targets[] = $target;

    }

    public function removeTarget($target){

        $hash = spl_object_hash($target);

        $this->targets = array_filter($this->targets, function($item) use ($hash){
            return (spl_object_hash($item) == $hash);
        });

    }

    public function __call($method, $args){

        foreach($this->targets as $target){

            if(method_exists($target, $method)){
                return call_user_func_array([$target,$method], $args);
            }

        }

        throw new OutOfBoundsException("No object with method $method was added");

    }

}
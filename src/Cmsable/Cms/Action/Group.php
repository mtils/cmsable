<?php namespace Cmsable\Cms\Action;

use Traversable;
use OutOfRangeException;
use OutOfBoundsException;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use ArrayAccess;

class Group implements Countable, IteratorAggregate, ArrayAccess{

    protected $actions = array();

    protected $name = 'default';

    public function __construct($name = 'default'){
        $this->setName($name);
    }

    public function getName(){
        return $this->name;
    }

    public function setName($name){
        $this->name = $name;
        return $this;
    }

    public function append(Action $action){
        $this->actions[] = $action;
        return $this;
    }

    public function push(Action $action){
        return $this->append($action);
    }

    public function extend($actions){
        foreach($actions as $action){
            $this->append($action);
        }
        return $this;
    }

    public function insert($index, $action){

        $newArray = array();
        $pastInsertPosition=FALSE;
        $count = $this->count();

        if($index == $count){
            $this->append($action);
            return $this;
        }

        if($index > $count){
            throw new OutOfRangeException("Index $index not found");
        }

        for($i=0; $i<$count; $i++){
            if($i == $index){
                $newArray[$index] = $value;
                $newArray[$i+1] = $this->actions[$i];
                $pastInsertPosition = TRUE;
            }
            else{
                if(!$pastInsertPosition){
                    $newArray[$i] = $this->actions[$i];
                }
                else{
                    $newArray[$i+1] = $this->actions[$i];
                }
            }
        }
        if($pastInsertPosition){
            $this->actions = $newArray;
        }
        return $this;
    }

    public function remove(Action $action){
        return $this->pop($this->indexOf($action));
    }

    public function pop($index=NULL){

        if(is_null($index)){
            array_pop($this->actions);
            return $this;
        }

        $count = $this->count();
        $found = FALSE;
        for($i=0; $i<$count; $i++){
            if($i < $index){
                $newArray[$i] = $this->actions[$i];
            }
            if($i == $index){
                $found = TRUE;
            }
            if($i > $index){
                $newArray[$i-1] = $this->actions[$i];
            }
        }
        if($found){
            $this->actions = $newArray;
            return $this;
        }
    }

    public function indexOf(Action $action){
        $count = $this->count();
        $found = FALSE;
        for($i=0; $i<$count; $i++){
            if($action->name === $this->action[$i]->name){
                return $i;
            }
        }
        throw new OutOfBoundsException("Action $action->name not found");
    }

    public function contains(Action $action){

        try{
            return is_int($this->indexOf($action));
        }
        catch(OutOfBoundsException $e){
            return FALSE;
        }

    }

    public function count(){
        return count($this->actions);
    }

    public function getIterator(){
        return new ArrayIterator($this->actions);
    }

    public function offsetExists($offset){
        if(is_numeric($offset)){
            return isset($this->actions[$offset]);
        }
        return $this->contains($offset);
    }

    public function offsetGet($offset){
        if(is_numeric($offset)){
            return isset($this->actions[$offset]);
        }
        return $this->contains($offset);
    }

    public function offsetSet($offset, $value){
        $this->actions[$offset] = $value;
    }

    public function offsetUnset($offset){
        $this->pop($offset);
    }
}
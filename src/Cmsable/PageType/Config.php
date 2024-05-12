<?php namespace Cmsable\PageType;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Countable;
use ReturnTypeWillChange;

class Config implements ConfigInterface, ArrayAccess, Countable, IteratorAggregate{

    protected $pageTypeId;

    protected $original = [];

    protected $modified = [];

    public function getPageTypeId(){
        return $this->pageTypeId;
    }

    public function setPageTypeId($pageTypeId){

        $this->pageTypeId = $pageTypeId;
        return $this;

    }

    public function get($name){

        if(isset($this->modified[$name])){
            return $this->modified[$name];
        }

        if(isset($this->original[$name])){
            return $this->original[$name];
        }

    }

    public function set($name, $value){

        if(isset($this->original[$name])){
            if($value == $this->original[$name]){
                return $this;
            }
        }

        $this->modified[$name] = $value;

        return $this;

    }

    public function setFromModel($name, $value){

        $this->modified = array();

        $this->original[$name] = $value;

    }

    public function hasChanged($name){
        return isset($this->modified[$name]);
    }

    public function __get($name){
        return $this->get($name);
    }

    public function __set($name, $value){
        return $this->set($name, $value);
    }

    public function __isset($name){
        return isset($this->original[$name]);
    }

    #[ReturnTypeWillChange] public function offsetGet($offset){
        return $this->get($offset);
    }

    #[ReturnTypeWillChange] public function offsetSet($offset, $value){
        $this->set($offset, $value);
    }

    #[ReturnTypeWillChange] public function offsetExists($offset){
        return $this->__isset($offset);
    }

    #[ReturnTypeWillChange] public function offsetUnset($offset){

        if(isset($this->original[$offset])){
            unset($this->original[$offset]);
        }

        if(isset($this->modified[$offset])){
            unset($this->modified[$offset]);
        }
    }

    public static function create(){
        $class = get_called_class();
        return new $class();
    }

    #[ReturnTypeWillChange] public function getIterator(){

        $data = [];

        foreach($this->original as $key=>$value){
            $data[$key] = $this->get($key);
        }

        return new ArrayIterator($data);

    }

    #[ReturnTypeWillChange] public function count(){
        return count($this->original);
    }

}
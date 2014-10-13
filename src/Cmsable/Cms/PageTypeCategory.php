<?php namespace Cmsable\Cms;

use Config;

class PageTypeCategory{

    protected $name;

    public function __construct($name){
        $this->name = $name;
    }

    public function getName(){
        return $this->name;
    }

    public function getTitle(){
        $transPath = 'cmsable::pagetypes.categories.'.$this->getName();
        return trans($transPath);
    }

    public function getIcon(){
        $configPath = 'cmsable::pagetype-categories.'.$this->getName().'.icon';
        return Config::get($configPath);
    }

    public function __get($name){
        $method = 'get'.ucfirst($name);
        return $this->$method();
    }
}
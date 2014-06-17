<?php namespace Cmsable\Cms;

interface ControllerDescriptorLoaderInterface{
    public function all();
    public function byCategory();
    public function find($controllerClassName);
}
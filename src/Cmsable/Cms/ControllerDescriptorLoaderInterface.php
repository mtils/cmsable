<?php namespace Cmsable\Cms;

interface ControllerDescriptorLoaderInterface{
    public function get($id);
    public function has($id);
    public function all($routeScope='default');
    public function byCategory($routeScope='default');
    public function getCategory($name);
    public function getCategories($routeScope='default');
}
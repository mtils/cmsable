<?php namespace Cmsable\Cms;

interface ControllerDescriptorLoaderInterface{
    public function get($id);
    public function all($routeScope='default');
    public function byCategory($routeScope='default');
}
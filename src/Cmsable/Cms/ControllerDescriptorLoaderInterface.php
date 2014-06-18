<?php namespace Cmsable\Cms;

interface ControllerDescriptorLoaderInterface{
    public function get($id);
    public function all();
    public function byCategory();
}
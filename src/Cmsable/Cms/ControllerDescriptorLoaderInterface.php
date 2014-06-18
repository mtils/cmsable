<?php namespace Cmsable\Cms;

interface ControllerDescriptorLoaderInterface{
    public function get($id);
    public function all($treeScope=1);
    public function byCategory($treeScope=1);
}
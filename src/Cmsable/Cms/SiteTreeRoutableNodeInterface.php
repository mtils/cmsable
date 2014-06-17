<?php namespace Cmsable\Cms;

interface SiteTreeRoutableNodeInterface extends SiteTreeNodeInterface{

    public function getControllerClass();

    public function setControllerClass($className);
}
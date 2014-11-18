<?php namespace Cmsable\PageType;


interface ConfigInterface{

    public function getPageTypeId();

    public function setPageTypeId($pageTypeId);

    public function get($name);

    public function set($name, $value);

    public function setFromModel($name, $value);

    public function hasChanged($name);

}
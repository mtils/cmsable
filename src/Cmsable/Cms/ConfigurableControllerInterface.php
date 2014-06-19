<?php namespace Cmsable\Cms;

use ConfigurableClass\ConfigurableInterface;
use ConfigurableClass\ConfigInterface;
use FormObject\FieldList;
 
interface ConfigurableControllerInterface extends ConfigurableInterface{
    public function setConfig(ConfigInterface $config);
}
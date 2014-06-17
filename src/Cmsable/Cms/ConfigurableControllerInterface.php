<?php namespace Cmsable\Cms;

use ConfigurableClass\ConfigurableInterface;
use FormObject\FieldList;
 
interface ConfigurableControllerInterface extends ConfigurableInterface{
    public function getFieldPrefix();
    public function appendFormFields(FieldList $fields);
    public function appendValidatorRules($validator);
}
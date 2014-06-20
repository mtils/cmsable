<?php namespace Cmsable\Form\Plugin;

use FormObject\Form;
use Cmsable\Cms\ControllerDescriptor;

interface PluginInterface{

    public function setPageType(ControllerDescriptor $type);

    public function modifyForm(Form $form);

    public function fillForm(Form $form, $model);

    public function processSubmit(Form $form, $model);

}
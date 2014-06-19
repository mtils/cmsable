<?php namespace Cmsable\Cms;

use FormObject\Form;

interface FormPluginInterface{

    public function setPageType(ControllerDescriptor $type);

    public function modifyForm(Form $form);

    public function fillForm(Form $form, $model);

    public function processSubmit(Form $form, $model);

}
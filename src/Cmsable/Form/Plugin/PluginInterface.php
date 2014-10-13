<?php namespace Cmsable\Form\Plugin;

use FormObject\Form;
use Cmsable\Cms\PageType;

interface PluginInterface{

    public function setPageType(PageType $type);

    public function modifyForm(Form $form);

    public function fillForm(Form $form, $model);

    public function beforeSave(Form $form, $model);

    public function afterSave(Form $form, $model);

}
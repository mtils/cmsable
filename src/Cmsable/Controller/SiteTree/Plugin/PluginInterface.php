<?php namespace Cmsable\Controller\SiteTree\Plugin;

use FormObject\Form;
use FormObject\FieldList;
use FormObject\Validator\ValidatorInterface;

use Cmsable\Cms\PageType;
use Cmsable\Model\SiteTreeNodeInterface;

interface PluginInterface{

    public function setPageType(PageType $type);

    public function modifyFormFields(FieldList $fields, SiteTreeNodeInterface $page);

    public function modifyFormValidator(ValidatorInterface $validator, SiteTreeNodeInterface $page);

    public function modifyFormActions(FieldList $actions, SiteTreeNodeInterface $page);

    public function fillForm(Form $form, SiteTreeNodeInterface $page);

    public function prepareSave(Form $form, SiteTreeNodeInterface $page);

    public function finalizeSave(Form $form, SiteTreeNodeInterface $page);

    public function processPageTypeLeave(SiteTreeNodeInterface $page, $oldPageTypeId);

}
<?php namespace Cmsable\Form;

use FormObject\Form;

class PermissionFinder{

    protected $form;

    public function __construct(Form $form){
        $this->form = $form;
    }

    public function getViewPermissions(){
        return array('page.public-view','page.logged-view','cms.access');
    }

    public function getEditPermissions(){
        return array('page.edit','cms.access','superuser');
    }

    public function getDeletePermissions(){
        return array('page.delete','cms.access','superuser');
    }

    public function getAddChildPermissions(){
        return array('page.add-child','cms.access','superuser');
    }

    public function buildFormValues($permissions){

        $formValues = array();

        foreach($permissions as $perm){
            $key = str_replace('.', '-', $perm);
            $formValues[$perm] = trans("cmsable::permissions.$key");
        }

        return $formValues;
    }
}
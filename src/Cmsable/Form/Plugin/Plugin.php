<?php namespace Cmsable\Form\Plugin;

use FormObject\Form;
use FormObject\FieldList;
use Event;
use Cmsable\Cms\ControllerDescriptor;

class Plugin implements PluginInterface{

    protected $pageType;

    public function setPageType(ControllerDescriptor $type){
        $this->pageType = $type;
        return $this;
    }

    public function modifyFormFields(FieldList $fields){}

    public function modifyValidator($validator){}

    public function modifyForm(Form $form){

        $formName = $form->getName();
        $mod = $this;

        Event::listen("form.fields-created.$formName", function($fields) use ($mod){
            $mod->modifyFormFields($fields);
        });

        Event::listen("form.validator-created.$formName", function($validator) use ($mod){
            $mod->modifyValidator($validator);
        });
    }

    public function fillForm(Form $form, $model){
        
    }

    public function processSubmit(Form $form, $model){
        
    }

}
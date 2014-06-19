<?php namespace Cmsable\Cms;

use FormObject\Form;
use FormObject\FieldList;
use Event;
use App;

abstract class ConfigurableFormPlugin extends FormPlugin{

    private $_configType;

    private $_controller;

    private $_configModel;

    protected $_fieldPrefix = 'ctlsettings';

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

        $config = $this->configModel()->getConfig($this->controller(), $model->id);
        $form->fillByArray($config, $this->fieldPrefix());

    }

    public function processSubmit(Form $form, $model){

        $config = $this->configModel()->getConfig($this->controller(), $model->id);

        foreach($form->getData($this->fieldPrefix()) as $key=>$value){
            $config->set($key, $value);
        }

        $this->configModel()->saveConfig($config, $model->id);

    }

    protected function configType(){
        if(!$this->_configType){
            $this->_configType = $this->controller()->getConfigType();
        }
        return $this->_configType;
    }

    protected function controller(){
        if(!$this->_controller){
            $this->_controller = $this->pageType->createController(NULL);
        }
        return $this->_controller;
    }

    protected function configModel(){
        if(!$this->_configModel){
            $this->_configModel = App::make('ConfigurableClass\ConfigModelInterface');
        }
        return $this->_configModel;
    }

    protected function fieldName($name){
        return $this->fieldPrefix() . '__' . $name;
    }

    public function fieldPrefix(){
        return $this->_fieldPrefix;
    }

}
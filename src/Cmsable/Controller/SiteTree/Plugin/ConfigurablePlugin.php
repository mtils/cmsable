<?php namespace Cmsable\Controller\SiteTree\Plugin;;

use FormObject\Form;
use FormObject\FieldList;

use Cmsable\Model\SiteTreeNodeInterface;

use App;

abstract class ConfigurablePlugin extends Plugin{

    private $_configType;

    private $_controller;

    private $_configModel;

    private $_configTypeModel;

    protected $configurable;

    protected $controllerCreator;

    protected $_fieldPrefix = 'ctlsettings';

    public function fillForm(Form $form, SiteTreeNodeInterface $model){

        $config = $this->configModel()->getConfig($model->getPageTypeId(), $model->getIdentifier());
        $form->fillByArray($config, $this->fieldPrefix());

    }

    public function finalizeSave(Form $form, SiteTreeNodeInterface $model){

        $config = $this->configModel()->getConfig($model->getPageTypeId(), $model->getIdentifier());

        foreach($form->getData($this->fieldPrefix()) as $key=>$value){
            $config->set($key, $value);
        }

        $this->configModel()->saveConfig($config, $model->id);

    }

    protected function configType(){
        if(!$this->_configType){
            $this->_configType = $this->configTypeModel()->getConfigType($this->pageType);
        }
        return $this->_configType;
    }

    protected function controller(){
        if(!$this->_controller){
            $this->_controller = $this->pageType->createController(NULL);
        }
        return $this->_controller;
    }

    protected function getControllerCreator(){

        if(!$this->controllerCreator){
            $this->controllerCreator = App::make($this->pageType->getControllerCreatorClass());
        }

        return $this->controllerCreator;

    }

    protected function configModel(){
        if(!$this->_configModel){
            $this->_configModel = App::make('Cmsable\PageType\ConfigRepositoryInterface');
        }
        return $this->_configModel;
    }

    protected function configTypeModel(){
        if(!$this->_configTypeModel){
            $this->_configTypeModel = App::make('Cmsable\PageType\ConfigTypeRepositoryInterface');
        }
        return $this->_configTypeModel;
    }

    protected function fieldName($name){
        return $this->fieldPrefix() . '__' . $name;
    }

    public function fieldPrefix(){
        return $this->_fieldPrefix;
    }

}
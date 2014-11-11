<?php namespace Cmsable\Controller\SiteTree\Plugin;;

use FormObject\Form;
use FormObject\FieldList;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Cms\ConfigurableControllerInterface;
use ConfigurableClass\ConfigModelInterface;
use ConfigurableClass\ConfigurableInterface;

use App;

abstract class ConfigurablePlugin extends Plugin{

    private $_configType;

    private $_controller;

    private $_configModel;

    protected $configurable;

    protected $controllerCreator;

    protected $_fieldPrefix = 'ctlsettings';

    public function fillForm(Form $form, SiteTreeNodeInterface $model){

        $config = $this->configModel()->getConfig($this->getConfigurable(), $model->id);
        $form->fillByArray($config, $this->fieldPrefix());

    }

    public function finalizeSave(Form $form, SiteTreeNodeInterface $model){

        $config = $this->configModel()->getConfig($this->getConfigurable(), $model->id);

        foreach($form->getData($this->fieldPrefix()) as $key=>$value){
            $config->set($key, $value);
        }

        $this->configModel()->saveConfig($config, $model->id);

    }

    protected function configType(){
        if(!$this->_configType){
            $this->_configType = $this->getConfigurable()->getConfigType();
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

    protected function getConfigurable(){

        if($this->configurable){
            return $this->configurable;
        }

        $creator = $this->getControllerCreator();
        if($creator instanceof ConfigurableInterface){
            return $creator;
        }

        $controller = $this->controller();
        if($controller instanceof ConfigurableInterface){
            return $controller;
        }
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
<?php namespace Cmsable\Cms;

use App;

class PageType{

    protected $id;

    protected $_controllerClassName;

    protected $_singularName;

    protected $_pluralName;

    protected $_description;

    protected $_cmsIcon;

    protected $_allowdChildren;

    protected $_allowedParents;

    protected $_category;

    protected $_canBeRoot;

    protected $_controllerCreatorClass = 'Cmsable\Routing\ControllerCreator';

    protected $_formPluginClass = 'Cmsable\Form\Plugin\Plugin';

    protected $_controllerCreator;

    protected $_formPlugin;

    protected $_routeScope = 'default';

    protected $_targetPath = '';

    public function __construct($id=NULL){
        $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
        return $this;
    }

    public function getControllerClass(){
        return $this->_controllerClassName;
    }

    public function controllerClassName(){
        return $this->_controllerClassName;
    }

    public function setControllerClassName($className){
        $this->_controllerClassName = $className;
        return $this;
    }

    public function getTargetPath(){
        return $this->_targetPath;
    }

    public function setTargetPath($routePath){
        $this->_targetPath = $routePath;
        return $this;
    }

    public function singularName(){
        return $this->_singularName;
    }

    public function setSingularName($name){
        $this->_singularName = $name;
        return $this;
    }

    public function pluralName(){
        return $this->_pluralName;
    }

    public function setPluralName($name){
        $this->_pluralName = $name;
        return $this;
    }

    public function description(){
        return $this->_description;
    }

    public function setDescription($description){
        $this->_description = $description;
        return $this;
    }

    public function cmsIcon(){
        return $this->_cmsIcon;
    }

    public function setCmsIcon($icon){
        $this->_cmsIcon = $icon;
        return $this;
    }

    public function allowedChildren(){
        return $this->_allowdChildren;
    }

    public function setAllowedChildren(array $children){
        $this->_allowdChildren = $children;
        return $this;
    }

    public function allowedParents(){
        return $this->_allowedParents;
    }

    public function setAllowedParents($parents){
        $this->_allowedParents = $parents;
        return $this;
    }

    public function category(){
        return $this->_category;
    }

    public function setCategory($category){
        $this->_category = $category;
        return $this;
    }

    public function canBeRoot(){
        return $this->_canBeRoot;
    }

    public function setCanBeRoot($canBe){
        $this->_canBeRoot = $canBe;
        return $this;
    }

    public static function create($id){
        $class = get_called_class();
        return new $class($id);
    }

    public function getRouteScope(){
        return $this->_routeScope;
    }

    public function setRouteScope($scopeId){
        $this->_routeScope = $scopeId;
        return $this;
    }

    public function createController($page){
        return $this->getControllerCreator()->createController($this->controllerClassName(), $page);
    }

    public function getControllerCreator(){
        if(!$this->_controllerCreator){
            $this->_controllerCreator = App::make($this->getControllerCreatorClass());
        }
        return $this->_controllerCreator;
    }

    public function setControllerCreator($creator){
        $this->_controllerCreator = $creator;
        return $this;
    }

    public function getControllerCreatorClass(){
        return $this->_controllerCreatorClass;
    }

    public function setControllerCreatorClass($class){
        $this->_controllerCreatorClass = $class;
        return $this;
    }

    public function getFormPlugin(){
        if($this->_formPlugin === NULL){
            $this->_formPlugin = App::make($this->getFormPluginClass());
            $this->_formPlugin->setPageType($this);
        }
        return $this->_formPlugin;
    }

    public function getFormPluginClass(){
        return $this->_formPluginClass;
    }

    public function setFormPluginClass($className){
        $this->_formPluginClass = $className;
        return $this;
    }
}
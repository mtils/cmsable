<?php namespace Cmsable\Cms;

class ControllerDescriptor{

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

    public function __construct($id){
        $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
        return $this;
    }

    public function controllerClassName(){
        return $this->_controllerClassName;
    }

    public function setControllerClassName($className){
        $this->_controllerClassName = $className;
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

    public function create($id){
        $class = get_called_class();
        return new $class($id);
    }
}
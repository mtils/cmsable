<?php namespace Cmsable\Cms\Action;

class Action{

    protected $title;

    protected $name;

    protected $icon;

    protected $checked;

    protected $enabled;

    protected $checkable;

    protected $group;

    protected $visible = TRUE;

    protected $url;

    public function getTitle(){
        return $this->title;
    }

    public function setTitle($title){
        $this->title = $title;
        return $this;
    }

    public function getName(){
        return $this->name;
    }

    public function setName($name){
        $this->name = $name;
        return $this;
    }

    public function getIcon(){
        return $this->icon;
    }

    public function setIcon($icon){
        $this->icon = $icon;
        return $this;
    }

    public function isChecked(){
        return $this->checked;
    }

    public function isCheckable(){
        return $this->checkable;
    }

    public function setCheckable($checkable=TRUE){
        $this->checkable = TRUE;
        return $this;
    }

    public function getGroup(){
        return $this->group;
    }

    public function setGroup($group){
        $this->group = $group;
        return $this;
    }

    public function getUrl(){
        return $this->url;
    }

    public function setUrl($url){
        $this->url = $url;
        return $this;
    }

    public function isEnabled(){
        return $this->enabled;
    }

    public function setEnabled($enabled=TRUE){
        $this->enabled = $enabled;
        return $this;
    }

    public function isDisabled(){
        return !$this->isEnabled();
    }

    public function setDisabled($disabled=TRUE){
        return $this->setEnabled(!$disabled);
    }

    public function isVisible(){
        return $this->visible;
    }

    public function getVisible(){
        return $this->isVisible();
    }

    public function setVisible($visible=TRUE){
        $this->visible = $visible;
        return $this;
    }

    public function isHidden(){
        return !$this->isVisible();
    }

    public function getHidden(){
        return $this->isHidden;
    }

    public function setHidden($hidden=TRUE){
        return $this->setVisible(!$hidden);
    }

    public function __get($name){
        $method = "get$name";
        return $this->$method();
    }
}

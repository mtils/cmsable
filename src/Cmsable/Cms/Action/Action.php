<?php namespace Cmsable\Cms\Action;

use Collection\StringList;
use FormObject\Attributes;

class Action
{

    protected $title;

    protected $name;

    protected $icon;

    protected $checked;

    protected $enabled = TRUE;

    protected $checkable;

    protected $group;

    protected $visible = TRUE;

    protected $onClick = '';

    protected $url;

    protected $contexts;

    protected $data;

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

    public function getOnClick(){
        return $this->onClick;
    }

    public function setOnClick($onClick){
        $this->onClick = $onClick;
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

    public function getContexts(){
        if(!$this->contexts){
            $this->contexts = new StringList;
        }
        return $this->contexts;
    }

    public function showIn($contexts){

        if(is_array($contexts)){
            $this->contexts = new StringList($contexts);
        }
        elseif($contexts instanceof StringList){
            $this->contexts = $contexts;
        }
        elseif(func_num_args() > 1){
            $this->contexts = new StringList(func_get_args());
        }
        elseif(is_string($contexts)){
            $this->contexts = StringList::fromString($contexts);
        }
        return $this;
    }

    public function visibleIn($context){
        $contexts = (array)$context;
        foreach($contexts as $context){
            if($this->getContexts()->contains($context)){
                return true;
            }
        }
        return false;
    }

    public function getData($key=null)
    {
        $this->initData();

        if ($key === null) {
            return $this->data;
        }

        if (isset($this->data["data-$key"])) {
            return $this->data["data-$key"];
        }
    }

    public function setData($key, $value)
    {
        $this->initData();
        $this->data["data-$key"] = $value;
        return $this;
    }

    protected function initData()
    {
        if ($this->data) {
            return;
        }
        $this->data = new Attributes;
    }

    public function __get($name){
        $method = "get$name";
        return $this->$method();
    }

    public function __set($name, $value){
        $method = "set$name";
        return $this->{$method}($value);
    }
}

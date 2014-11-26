<?php namespace Cmsable\Routing\TreeScope;

class TreeScope{

    protected $modelRootId = 0;

    protected $type = 'default';

    protected $title = '';

    public function getModelRootId(){
        return $this->modelRootId;
    }

    public function setModelRootId($id){
        $this->modelRootId = $id;
        return $this;
    }

    public function getType(){
        return $this->type;
    }

    public function setType($type){
        $this->type = $type;
        return $this;
    }

    public function getTitle(){
        return $this->title;
    }

    public function setTitle($title){
        $this->title = $title;
        return $this;
    }

}
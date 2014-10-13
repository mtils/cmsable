<?php namespace Cmsable\Routing\Routable;

use Cmsable\Model\SiteTreeNodeInterface;
use Exception;

class Routable{

    public $pageType;

    public $node;

    public $controllerPath;

    public $executor;

    public $executeMethod;

    public $params = [];

    public function getPageType(){
        return $this->pageType;
    }

    public function setPageType($pageType){
        $this->pageType = $pageType;
        return $this;
    }

    public function getNode(){
        return $this->node;
    }

    public function setNode(SiteTreeNodeInterface $node){
        $this->node = $node;
        return $this;
    }

    public function getControllerPath(){
        return $this->controllerPath;
    }

    public function setControllerPath($path){
        $this->controllerPath = $path;
        return $this;
    }

    public function getExecutor(){
        return $this->executor;
    }

    public function setExecutor($executor){
        $this->executor = $executor;
        return $this;
    }

    public function getExecuteMethod(){
        return $this->executeMethod;
    }

    public function setExecuteMethod($method){
        $this->executeMethod = $method;
        return $this;
    }

    public function getParams(){
        return $this->params;
    }

    public function setParams($params){
        $this->params = (array)$params;
        return $this;
    }

    public function isSameController(){

        $defaultController = $this->pageType->getControllerClass();
        $actualController = $this->executor;

        if(is_string($defaultController) && is_string($actualController)
           && trim($defaultController,'\\') == trim($actualController,'\\')){
           return TRUE;
        }
        return FALSE;

    }

    public function isIndex(){
        return ($this->executeMethod == 'getIndex');
    }

    public function __toString(){

        try{
            $string  = "pageType: " . $this->getPageType()->getId();
            $string .= "\nnode.path: ". $this->getNode()->getPath();
            $string .= "\ncontrollerPath: ". $this->getControllerPath();
            $string .= "\nexecutor: " .$this->getExecutor();
            $string .= "\nexecutorMethod: " .$this->getExecuteMethod();
            $string .= "\nparams: " . var_export($this->getParams(), true);
        }
        catch(Exception $e){
            return $e->getMessage();
        }

        return $string;
    }

}
<?php namespace Cmsable\Routing;

use App;
use Cmsable\Cms\ConfigurableControllerInterface;
use ConfigurableClass\ConfigModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;

class ControllerCreator{

    public function createController($name, $page){

        $controller = App::make($name);

        if($this->isConfigurable($controller)){
            $this->bootConfigurableController($controller, $page);
        }

        return $controller;

    }

    protected function bootConfigurableController($controller, $page){

        if($page instanceof SiteTreeNodeInterface){
            $id = $page->getIdentifier();
        }
        else{
            $id = NULL;
        }

        $config = App::make('ConfigurableClass\ConfigModelInterface')->getConfig($controller, $id);
        $controller->setConfig($config);

    }

    protected function isConfigurable($controller){

        // First check for performance reasons. Otherwise it will load all interfaces
        if(method_exists($controller, 'setConfig')){
            if($controller instanceof ConfigurableControllerInterface){
                return TRUE;
            }
        }
        return FALSE;
    }
}
<?php namespace Cmsable\Routing;

use App;
use Cmsable\Cms\ConfigurableControllerInterface;
use ConfigurableClass\ConfigModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;
use PageTypes;

class ControllerCreator implements ControllerCreatorInterface{

    /**
     * @brief Creates a controller while routing to a page. This method is used
     *        to configure dependencies of you controller according to a page.
     *        You can also configure your controller directly like setting a
     *        a layout
     *
     * @param string $controllerName The classname of the routed controller
     * @param \Cmsable\Model\SiteTreeNodeInterface $page (optional) Null if no match
     * @return \Illuminate\Routing\Controller
     **/
    public function createController($name, SiteTreeNodeInterface $page=NULL){

        $controller = App::make($name);

        if($this->isConfigurable($controller)){
            $this->bootConfigurableController($controller, $page);
        }
        else{
            PageTypes::resetCurrentPageConfig();
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
        PageTypes::setCurrentPageConfig($config);

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
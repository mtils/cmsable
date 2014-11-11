<?php namespace Cmsable\Routing;

use App;
use Cmsable\Cms\ConfigurableControllerInterface;
use ConfigurableClass\ConfigModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;
use ConfigurableClass\ConfigurableInterface;
use PageTypes;

abstract class ConfigurableControllerCreator implements ControllerCreatorInterface, ConfigurableInterface{

    protected $configModel;

    protected $config;

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
    abstract public function createController($name, SiteTreeNodeInterface $page=NULL);

    abstract public function getConfigType();

    protected function getConfig($page){

        if(!$this->config){

            $id = ($page instanceof SiteTreeNodeInterface) ? $page->getIdentifier() : NULL;

            $this->config = $this->configModel()->getConfig($this, $id);

            if($this->config){
                PageTypes::setCurrentPageConfig($this->config);
            }
            else{
                PageTypes::resetCurrentPageConfig();
            }

        }

        return $this->config;

    }

    public function getClassName(){
        return get_class($this);
    }

    protected function configModel(){

        if(!$this->configModel){
            $this->configModel = App::make('ConfigurableClass\ConfigModelInterface');
        }

        return $this->configModel;

    }
}
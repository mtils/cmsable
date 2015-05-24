<?php namespace Cmsable\Routing;

use App;
use Cmsable\Model\SiteTreeNodeInterface;


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

        return $controller;

    }

}
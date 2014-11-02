<?php namespace Cmsable\Routing;

use Cmsable\Model\SiteTreeNodeInterface;

interface ControllerCreatorInterface{

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
    public function createController($controllerName, SiteTreeNodeInterface $page=NULL);

}
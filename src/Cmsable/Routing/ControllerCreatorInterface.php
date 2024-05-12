<?php namespace Cmsable\Routing;

use Cmsable\Model\SiteTreeNodeInterface;
use Illuminate\Routing\Controller;

interface ControllerCreatorInterface{

    /**
     * @brief Creates a controller while routing to a page. This method is used
     *        to configure dependencies of you controller according to a page.
     *        You can also configure your controller directly like setting a
     *        a layout
     *
     * @param string $controllerName The classname of the routed controller
     * @param SiteTreeNodeInterface|null $page (optional) Null if no match
     * @return Controller
     */
    public function createController($controllerName, SiteTreeNodeInterface $page=NULL);

}
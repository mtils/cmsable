<?php namespace Cmsable\Routing;

interface RouteInspectorInterface{

    /**
    * @brief Returns if the route or all are currently inside the SiteTree
    *
    * @return bool
    */
    public function inSiteTree();

    /**
    * @brief Returns the matches SiteTree-Node of this request.
    *        This is always a existing node of the SiteTree, no matter how
    *        many SubRoutes or actions a appended to it.
    * 
    * @return \Cmsable\Model\SiteTreeInterface
    */
    public function getMatchedNode();

    /**
    * @brief Returns the matched Routable of current request.
    * 
    * @return \Cmsable\Routing\Routable\Routable
    */
    public function getMatchedRoutable();
}
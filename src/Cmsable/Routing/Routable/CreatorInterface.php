<?php namespace Cmsable\Routing\Routable;

use Illuminate\Http\Request;
use Cmsable\Cms\PageType;
use Cmsable\Model\SiteTreeNodeInterface;

interface CreatorInterface{

    /**
    * @brief Creates a Routable object which is used to invoke a controller
    * 
    * @param Request $request
    * @param SiteTreeNodeInterface $node
    * @param string $parsedPath
    * @return Routable
    */
    public function createRoutable(Request $request, SiteTreeNodeInterface $node, $parsedPath);

}
<?php namespace Cmsable\Routing\Routable;

use Illuminate\Http\Request;
use Cmsable\Cms\PageType;
use Cmsable\Model\SiteTreeNodeInterface;

class PathEqualsCreator extends AbstractCreator implements CreatorInterface{

    /**
    * @brief Creates a Routable object which is used to invoke a controller
    * 
    * @param Request $request
    * @param SiteTreeNodeInterface $node
    * @param string $parsedPath
    * @return Routable
    */
    public function createRoutable(Request $request, SiteTreeNodeInterface $node, $parsedPath){

        if($node->getPath() == $parsedPath){

            $pageType = $this->getPageType($node);
            $executor = $pageType->getControllerClass();

            $routable = new Routable;
            $routable->setPageType($pageType);
            $routable->setNode($node);
            $routable->setControllerPath($parsedPath);
            $routable->setExecutor($executor);
            $routable->setExecuteMethod($this->getDefaultMethod($request, $executor));

            return $routable;
        }

    }

}
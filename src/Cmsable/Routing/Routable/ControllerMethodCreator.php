<?php namespace Cmsable\Routing\Routable;

use Illuminate\Http\Request;
use Cmsable\Cms\PageType;
use Cmsable\Model\SiteTreeNodeInterface;

class ControllerMethodCreator extends AbstractCreator implements CreatorInterface{

    /**
    * @brief Creates a Routable object which is used to invoke a controller
    * 
    * @param Request $request
    * @param SiteTreeNodeInterface $node
    * @param string $parsedPath
    * @return Routable
    */
    public function createRoutable(Request $request, SiteTreeNodeInterface $node, $parsedPath){

        if($parsedPath != $node->getPath() && starts_with($parsedPath, $node->getPath())){

            $remainingPath = $this->getNonSiteTreePath($node, $parsedPath);
            $firstSegment = $this->getFirstSegment($remainingPath);

            $pageType = $this->getPageType($node);
            $executor = $pageType->getControllerClass();

            if($controllerMethod = $this->getControllerMethod($executor, $firstSegment, $this->getVerb($request))){

                $remainingPath = $this->getNextPart($remainingPath);

                $routable = new Routable;
                $routable->setPageType($pageType);
                $routable->setNode($node);
                $routable->setControllerPath($node->getPath());
                $routable->setExecutor($executor);
                $routable->setExecuteMethod($controllerMethod);
                $routable->setParams($this->parseParams($remainingPath));

                return $routable;

            }
        }

    }

}
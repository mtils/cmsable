<?php namespace Cmsable\Routing;

use Cmsable\Http\CmsRequestInterface;
use Illuminate\Routing\Route;
use Cmsable\PageType\RepositoryInterface;

class PageTypeRouter
{

    /**
     * @var \Cmsable\PageType\RepositoryInterface
     **/
    protected $pageTypes;

    public function __construct(RepositoryInterface $pageTypes)
    {
        $this->pageTypes = $pageTypes;
    }

    public function setPageType(Route $route, CmsRequestInterface $request)
    {

        if (!$cmsPath = $request->getCmsPath()) {
            return;
        }

        // If a page match did happen the request already has a pagetype
        if ($cmsPath->isCmsPath()) {
            return;
        }

        if (!$pageType = $this->pageTypes->getByRouteName($route->getName())) {
            return;
        }

        $request->getCmsPath()->setPageType($pageType);
    }

}
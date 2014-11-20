<?php namespace Cmsable\PageType;

use Cmsable\Model\SiteTreeNodeInterface;

interface CurrentPageTypeProviderInterface{

    /**
     * Returns the page config of SiteTreeNodeInterface $page
     * If no page is passed, the config of current page is returned
     *
     * @return \Cmsable\PageType\PageType
     **/
    public function current();

    /**
     * Returns the pagetype config of PageType $pageType
     * If no page is passed, the config of current page is returned
     *
     * @return \Cmsable\PageType\ConfigInterface
     **/
    public function currentConfig();

}
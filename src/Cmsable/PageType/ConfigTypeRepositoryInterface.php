<?php namespace Cmsable\PageType;

interface ConfigTypeRepositoryInterface{

    /**
     * Get the config TYPE of page-type $pageType
     *
     * @param mixed $pageType PageType or pageTypeId
     * @return \XType\NamedFieldType
     **/
    public function getConfigType($pageType);

}
<?php namespace Cmsable\PageType;

use XType\NamedFieldType;

interface ConfigTypeRepositoryInterface{

    /**
     * Get the config TYPE of page-type $pageType
     *
     * @param mixed $pageType PageType or pageTypeId
     * @return NamedFieldType
     **/
    public function getConfigType($pageType);

}
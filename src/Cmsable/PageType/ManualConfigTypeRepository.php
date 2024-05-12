<?php namespace Cmsable\PageType;

use OutOfBoundsException;

use XType\NamedFieldType;

class ManualConfigTypeRepository implements ConfigTypeRepositoryInterface{

    protected $configTypes = [];

    /**
     * Get the config TYPE of page-type $pageType
     *
     * @param mixed $pageType PageType or pageTypeId
     * @return NamedFieldType
     **/
    public function getConfigType($pageType){

        $id = $this->pageTypeId($pageType);

        if(!isset($this->configTypes[$id])){
            throw new OutOfBoundsException("Pagetype '$id' not found");
        }

        return $this->configTypes[$id];

    }

    public function setConfigType($pageType, NamedFieldType $type){

        $this->configTypes[$this->pageTypeId($pageType)] = $type;

        return $this;

    }

    protected function pageTypeId($pageType){
        return ($pageType instanceof PageType) ? $pageType->getId() : $pageType;
    }

}
<?php namespace Cmsable\PageType;

use OutOfBoundsException;

use XType\NamedFieldType;
use XType\Factory\TemplateFactory;

class TemplateConfigTypeRepository implements ConfigTypeRepositoryInterface {

    /**
     * @var \XType\Factory\TemplateFactory
     **/
    protected $typeFactory;

    protected $configTypes = [];

    protected $templates = [];

    public function __construct(TemplateFactory $typeFactory){

        $this->typeFactory = $typeFactory;

    }

    /**
     * Get the config TYPE of page-type $pageType
     *
     * @param mixed $pageType PageType or pageTypeId
     * @return \XType\NamedFieldType
     **/
    public function getConfigType($pageType){

        $id = $this->pageTypeId($pageType);

        if(isset($this->configTypes[$id])){
            return $this->configTypes[$id];
        }

        if(!isset($this->templates[$id])){
            throw new OutOfBoundsException("Config of PageType '$id' not found");
        }

        return $this->typeFactory->create($this->templates[$id]);

    }

    public function getTemplate($pageType){

        $id = $this->pageTypeId($pageType);

        if(!isset($this->templates[$id])){
            throw new OutOfBoundsException("Template of PageType '$id' not found");
        }

        return $this->templates[$id];
    }

    public function setTemplate($pageType, array $template){

        $this->templates[$this->pageTypeId($pageType)] = $template;

        return $this;

    }

    public function fillByArray(array $templates, $pageTypeIdKey='', $templateKey=''){

        if($pageTypeIdKey && $templateKey){

            foreach($templates as $template){

                if( isset($template[$pageTypeIdKey]) && isset($template[$templateKey])){

                    $this->setTemplate($template[$pageTypeIdKey],
                                    $template[$templateKey]);

                }

            }

            return $this;
        }

        foreach($templates as $pageTypeId=>$template){
            $this->setTemplate($pageTypeId, $template);
        }

        return $this;

    }

    public function setConfigType($pageType, NamedFieldType $type){

        $this->configTypes[$this->pageTypeId($pageType)] = $type;

        return $this;

    }

    protected function pageTypeId($pageType){
        return ($pageType instanceof PageType) ? $pageType->getId() : $pageType;
    }

}
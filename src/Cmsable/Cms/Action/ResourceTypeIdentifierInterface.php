<?php namespace Cmsable\Cms\Action;

interface ResourceTypeIdentifierInterface{

    /**
     * @brief Returns an id for an resourcetype to identify it
     *
     * @param mixed $resourceType 
     * @return string
     **/
    public function identifyItem($resource);

    /**
     * @brief Returns an id for a collection
     *
     * @param Traversable $resource
     * @return string
     **/
    public function identifyCollection($resource);

}
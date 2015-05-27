<?php namespace Cmsable\Resource\Contracts;

interface ReceivesResourceMapper
{

    /**
     * Sets the resource mapper when resolved
     *
     * @param \Cmsable\Resource\Contracs\ResourceMapper $mapper
     * @return void
     **/
    public function setResourceMapper(ResourceMapper $mapper);

}
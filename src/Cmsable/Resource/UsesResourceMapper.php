<?php namespace Cmsable\Resource;


use Cmsable\Resource\Contracts\ResourceMapper as MapperContract;

trait UsesResourceMapper
{

    protected $resourceMapper;

    public function getResourceMapper()
    {
        return $this->resourceMapper;
    }

    public function setResourceMapper(MapperContract $mapper)
    {
        $this->resourceMapper = $mapper;
    }

}
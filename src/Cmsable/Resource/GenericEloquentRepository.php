<?php


namespace Cmsable\Resource;


class GenericEloquentRepository extends EloquentRepository
{

    protected $model;

    protected $resourceName;

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function resourceName()
    {
        return $this->resourceName;
    }

    public function setResourceName($name)
    {
        $this->resourceName = $name;
    }

    public function with($model, $resource)
    {
        $this->setModel($model);
        $this->setResourceName($resource);
        return $this;
    }

}
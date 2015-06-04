<?php namespace Cmsable\Resource\Contracts;

interface ModelClassFinder
{

    /**
     * Find a model by its id. Should return null if not found
     *
     * @param mixed $id
     * @return object|null
     **/
    public function modelClass($resource);


}
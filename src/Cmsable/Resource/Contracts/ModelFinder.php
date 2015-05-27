<?php namespace Cmsable\Resource\Contracts;

interface ModelFinder
{

    /**
     * Find a model by its id. Should return null if not found
     *
     * @param mixed $id
     * @return object|null
     **/
    public function find($id);

}
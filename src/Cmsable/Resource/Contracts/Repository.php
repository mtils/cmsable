<?php namespace Cmsable\Resource\Contracts;

use Cmsable\Resource\Contracts\ModelFinder;

/** 
 * A Resource Repository a repository specialized to edit RESTful
 * resources.
 **/
interface Repository extends ModelFinder
{

    /**
     * Instantiate a new resource  and fill it with the attributes
     *
     * @param array $attributes
     * @return mixed The instantiated resource
     **/
    public function make(array $attributes=[]);

    /**
     * Create a new resource by the given attributes
     *
     * @param array $attributes
     * @return mixed The created resource
     **/
    public function store(array $attributes=[]);

    /**
     * Update the resource with id $id with new attributes $attributes
     * Return the resource after updating it. Must throw an exception if not found
     *
     * @param mixed $model
     * @param array $newAttributes
     * @return mixed The updated resource
     **/
    public function update($model, array $newAttributes);

    /**
     * Delete the resource with id $id. Throw an exception if not found.
     *
     * @param mixed $model
     * @return mixed The deleted resource
     **/
    public function delete($model);

}
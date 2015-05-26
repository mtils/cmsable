<?php namespace Cmsable\Model\Resource;

/** 
 * A Manager is something like a repository specialized to edit RESTful
 * resources. Always throw exceptions and handle resource level validation
 * inside a manager
 **/
interface ManagerInterface
{

    /**
     * Return the resource with id $id. Throw an exception if resource not found
     *
     * @return mixed The resource
     **/
    public function findOrFail($id);

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
     * @param mixed $id
     * @param array $newAttributes
     * @return mixed The updated resource
     **/
    public function update($id, array $newAttributes);

    /**
     * Delete the resource with id $id. Throw an exception if not found.
     *
     * @param mixed $id
     * @return mixed The deleted resource
     **/
    public function delete($id);

}
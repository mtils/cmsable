<?php namespace Cmsable\Resource\Contracts;

interface Mapper
{


    public function modelClass($resource);

    public function resourceOfModelClass($modelClass);

    public function formClass($resource);

    public function searchFormClass($resource);

    public function validationRules($resource);

    /**
     * Map resource to model class $class
     *
     * @param string $resource
     * @param string $class
     * @return self
     **/
    public function mapModelClass($resource, $class);

    /**
     * Manually map $resource to $formClass
     *
     * @param string $resource
     * @param string $formClass
     * @return self
     **/
    public function mapFormClass($resource, $formClass);

    /**
     * Manually map $resource to $searchFormClass
     *
     * @param string $resource
     * @param string $formClass
     * @return self
     **/
    public function mapSearchFormClass($resource, $searchFormClass);

    /**
     * Manually map the rules to $resource
     *
     * @param string $resource
     * @param array $rules
     * @return self
     **/
    public function mapValidationRules($resource, array $rules);

}
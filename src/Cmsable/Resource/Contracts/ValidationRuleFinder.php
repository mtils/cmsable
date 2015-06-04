<?php namespace Cmsable\Resource\Contracts;

interface ValidationRuleFinder
{

    /**
     * Return the validation rules for resource $resource. If a $modelClass is
     * known it will be passed as second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return array
     **/
    public function validationRules($resource, $modelClass=null);

}
<?php namespace Cmsable\Resource\Contracts;

interface ValidatorClassFinder
{

    /**
     * Finds the validator for $resource. If a modelClass is known it will be passed
     * as the second parameter.
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return \FormObject\Form
     **/
    public function validatorClass($resource, $modelClass=null);

}
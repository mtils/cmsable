<?php namespace Cmsable\Resource\Contracts;

use FormObject\Form;

/**
 * A FormFinder finds forms for resources
 * It allows that you can just call Resource::form() and get your form.
 **/
interface FormClassFinder
{

    /**
     * Finds the form to edit $resource. If a modelClass is known it will be passed
     * as the second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return Form
     **/
    public function formClass($resource, $modelClass=null);

    /**
     * Find the form to search a collection of $resource objects. If a modelClass
     * is known it will be passed as the second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return Form
     **/
    public function searchFormClass($resource, $modelClass=null);

}
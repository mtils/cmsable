<?php namespace Cmsable\Resource\Contracts;

interface Distributor
{

    /**
     * Return a form to edit $resource. If you pass $model it will be assigned
     * to the form via setModel
     *
     * @param mixed $model (optional)
     * @param string $resource (optional)
     * @return \FormObject\Form
     **/
    public function form($model=null, $resource=null);

    /**
     * Return if a form for $resource exists
     *
     * @param string $resource (optional)
     * @return bool
     **/
    public function hasForm($resource=null);

    /**
     * Return a form to search $resource.
     *
     * @param string $resource (optional)
     * @return \FormObject\Form
     **/
    public function searchForm($resource=null);

    /**
     * Return if a search form for $resource exists
     *
     * @param string $resource (optional)
     * @return bool
     **/
    public function hasSearchForm($resource=null);

    /**
     * Return a validator to validate a request when updating or creating
     * $resource
     *
     * @param string $resource (optional)
     * @return \Cmsable\Resource\Contracts\Validator
     **/
    public function validator($resource=null);

    /**
     * Return the validator rules for $resource
     *
     * @param string $resource (optional)
     * @return array
     **/
    public function rules($resource=null);

    /**
     * Set manuel rules for resource. If you set the rules manually you dont
     * have to write a validator. A generic one will be created
     *
     * @param string $resource
     * @param array $rules
     * @return self
     **/
    public function setRules($resource, array $rules);

    /**
     * Return the current resource. It will be detected if not set manually
     *
     * @return string
     **/
    public function getCurrentResource();

    /**
     * Manually set the current resource (mostly not needed)
     *
     * @param string $resource
     * @return self
     **/
    public function setCurrentResource($resource);

}
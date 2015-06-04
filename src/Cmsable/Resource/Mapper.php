<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\Mapper as MapperContract;

class Mapper implements MapperContract
{

    protected $models = [];

    protected $forms = [];

    protected $searchForms = [];

    protected $rules = [];

    public function modelClass($resource)
    {
        if (isset($this->models[$resource])) {
            return $this->models[$resource];
        }
    }

    public function resourceOfModelClass($modelClass)
    {
        foreach ($this->models as $resource=>$class) {
            if ($class == $modelClass) {
                return $resource;
            }
        }
    }

    public function formClass($resource)
    {
        if (isset($this->forms[$resource])) {
            return $this->forms[$resource];
        }
    }

    public function searchFormClass($resource)
    {
        if (isset($this->searchForms[$resource])) {
            return $this->searchForms[$resource];
        }
    }

    public function validationRules($resource)
    {
        if (isset($this->rules[$resource])) {
            return $this->rules[$resource];
        }
    }

    /**
     * Map resource to model class $class
     *
     * @param string $resource
     * @param string $class
     * @return self
     **/
    public function mapModelClass($resource, $class)
    {
        $this->models[$resource] = $class;
        return $this;
    }

    /**
     * Manually map $resource to $formClass
     *
     * @param string $resource
     * @param string $formClass
     * @return self
     **/
    public function mapFormClass($resource, $formClass)
    {
        $this->forms[$resource] = $formClass;
        return $this;
    }

    /**
     * Manually map $resource to $searchFormClass
     *
     * @param string $resource
     * @param string $formClass
     * @return self
     **/
    public function mapSearchFormClass($resource, $searchFormClass)
    {
        $this->searchForms[$resource] = $searchFormClass;
        return $this;
    }

    /**
     * Manually map the rules to $resource
     *
     * @param string $resource
     * @param array $rules
     * @return self
     **/
    public function mapValidationRules($resource, array $rules)
    {
        $this->rules[$resource] = $rules;
        return $this;
    }

}
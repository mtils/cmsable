<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ClassFinder as ClassFinderContract;
use Cmsable\Resource\Contracts\FormClassFinder;
use Cmsable\Resource\Contracts\ModelClassFinder;
use Cmsable\Resource\Contracts\ValidatorClassFinder;
use FormObject\Form;

class ClassFinder implements ClassFinderContract
{

    protected $formFinder;

    protected $modelFinder;

    protected $validatorFinder;

    public function __construct(FormClassFinder $formFinder,
                                ModelClassFinder $modelFinder,
                                ValidatorClassFinder $validatorFinder)
    {
        $this->formFinder = $formFinder;
        $this->modelFinder = $modelFinder;
        $this->validatorFinder = $validatorFinder;
    }

    /**
     * Finds the form to edit $resource. If a modelClass is known it will be passed
     * as the second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return Form
     **/
    public function formClass($resource, $modelClass=null)
    {
        return $this->formFinder->formClass($resource, $modelClass);
    }

    /**
     * Find the form to search a collection of $resource objects. If a modelClass
     * is known it will be passed as the second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return Form
     **/
    public function searchFormClass($resource, $modelClass=null)
    {
        return $this->formFinder->searchFormClass($resource, $modelClass);
    }

    /**
     * Find a model by its id. Should return null if not found
     *
     * @param mixed $id
     * @return object|null
     **/
    public function modelClass($resource)
    {
        return $this->modelFinder->modelClass($resource);
    }

    /**
     * Return the validation rules for resource $resource. If a $modelClass is
     * known it will be passed as second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return array
     **/
    public function validatorClass($resource, $modelClass=null)
    {
        return $this->validatorFinder->validatorClass($resource, $modelClass);
    }

}
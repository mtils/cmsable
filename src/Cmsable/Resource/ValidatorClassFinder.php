<?php namespace Cmsable\Resource;

use Signal\Support\FindsClasses;
use Cmsable\Resource\Contracts\ValidatorClassFinder as ClassFinderContract;

class ValidatorClassFinder implements ClassFinderContract
{

    use FindsClasses;

    protected $namespaces = ['App\Validators'];

    /**
     * Finds the validator for $resource. If a modelClass is known it will be passed
     * as the second parameter.
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return \FormObject\Form
     **/
    public function validatorClass($resource, $modelClass=null)
    {
        if ($modelClass) {
            return $this->findClass($modelClass.'Validator');
        }
        return $this->findClass($this->camelCase($resource).'Validator');
    }

}
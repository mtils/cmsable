<?php namespace Cmsable\Resource;

use Signal\Support\FindsClasses;
use Cmsable\Resource\Contracts\FormClassFinder as ClassFinderContract;
use Cmsable\Support\FindsResourceClasses;

class FormClassFinder implements ClassFinderContract
{

    use FindsResourceClasses;

    protected $namespaces = ['App\Http\Forms'];

    /**
     * Finds the form to edit $resource. If a modelClass is known it will be passed
     * as the second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return \FormObject\Form
     **/
    public function formClass($resource, $modelClass=null)
    {
        if ($modelClass) {
            return $this->findClass($modelClass.'Form');
        }
        return $this->findClass($this->className($resource).'Form');
    }

    /**
     * Find the form to search a collection of $resource objects. If a modelClass
     * is known it will be passed as the second parameter
     *
     * @param string $resource
     * @param string $modelClass (optional)
     * @return \FormObject\Form
     **/
    public function searchFormClass($resource, $modelClass=null)
    {
        if ($modelClass) {
            return $this->findClass($modelClass.'SearchForm');
        }
        return $this->findClass($this->className($resource).'SearchForm');
    }

}
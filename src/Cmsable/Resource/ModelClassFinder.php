<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ModelClassFinder as ClassFinderContract;
use Signal\Support\FindsClasses;

class ModelClassFinder implements ClassFinderContract
{

    use FindsClasses;

    protected $namespaces = ['App'];

    /**
     * Find a model by its id. Should return null if not found
     *
     * @param mixed $id
     * @return object|null
     **/
    public function modelClass($resource)
    {
        return $this->findClass($this->resourceToClass($resource));
    }

    public function resourceToClass($resource)
    {
        $class = ucfirst(camel_case($resource));
        return substr($class, 0, strlen($class)-1);
    }

}
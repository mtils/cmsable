<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ModelClassFinder as ClassFinderContract;
use Signal\Support\FindsClasses;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        if (strpos($resource, '.') !== false) {
            return $this->nestedResourceToClass($resource);
        }

        $class = ucfirst(camel_case($resource));
        return substr($class, 0, strlen($class)-1);
    }

    protected function nestedResourceToClass($resource)
    {
        $tiles = explode('.', $resource);

        $first = array_shift($tiles);

        if (!$rootClass = $this->findClass($this->unnestedResourceToClass($first))) {
            return;
        }

        $rootObject = new $rootClass;
        $currentObject = &$rootObject;

        // Check for Eloquent relations
        foreach ($tiles as $method) {

            if (!method_exists($currentObject, $method)) {
                return;
            }

            $relation = $currentObject->$method();

            if (!$relation instanceof Relation) {
                return;
            }

            $related = $relation->getRelated();

            $currentObject = $related;

        }

        return class_basename($currentObject);

    }

    protected function unnestedResourceToClass($resource)
    {
        $class = ucfirst(camel_case($resource));
        return substr($class, 0, strlen($class)-1);
    }

}
<?php namespace Cmsable\Model;

use OutOfBoundsException;
use Illuminate\Http\Request;

class ResourceMapper implements ResourceMapperInterface
{

    protected $routeMap = [];

    protected $modelMap = [];

    /**
     * {@inheritdoc}
     *
     * @param Illuminate\Http\Request
     * @return string
     **/
    public function resourceByRequest(Request $request)
    {
        $routeName = $this->getRouteName($request);

        if (isset($this->routeMap[$routeName])) {
            return $this->routeMap[$routeName];
        }

        $this->routeMap[$routeName] = $this->calculateResourceName($routeName);

        return $this->routeMap[$routeName];
    }

    /**
     * {@inheritdoc}
     *
     * @param object|string $model (classname)
     * @return string
     **/
    public function resourceByModel($model)
    {

        $class = $this->getClass($model);

        if (isset($this->modelMap[$class])) {
            return $this->modelMap[$class];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource name
     * @return string classname of model
     **/
    public function modelByResource($resource)
    {

        foreach ($this->modelMap as $class=>$res) {
            if ($res == $resource) {
                return $class;
            }
        }

    }

    public function mapToRoute($resource, $routeName)
    {
        $this->routeMap[$routeName] = $resource;
    }

    public function mapToModel($resource, $class)
    {
        $this->modelMap[$this->getClass($class)] = $resource;
    }

    protected function calculateResourceName($routeName)
    {

        if (strpos($routeName, '.') === false) {
            return $routeName;
        }

        return explode('.', $routeName)[0];
    }

    protected function getRouteName(Request $request)
    {
        if (!$route = $request->route()) {
            throw new OutOfBoundsException("Request has no route, no chance to get the resource name");
        }
        return $route->getName();
    }

    protected function getClass($class)
    {
        return is_object($class) ? get_class($class) : ltrim($class,'\\');
    }

    protected function getObject($object)
    {
        return is_object($object) ? $object : new $object;
    }
}
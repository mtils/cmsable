<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\Detector;
use Illuminate\Http\Request;

class PathDetector implements Detector
{

    protected $notFoundHandler;

    protected $routeMap = [];

    /**
     * Return the resource name which is currently handled by $request
     * It defaults to the first segment of your route name
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
     * Manually map $routeName to $resource
     *
     * @param string $routeName
     * @param string $resource
     * @return self
     **/
    public function mapToRoute($routeName, $resource)
    {
        $this->routeMap[$routeName] = $resource;
        return $this;
    }

    /**
     * If the $resource could not be found, try it with this callable
     *
     * @param callable $handler
     * @return self
     **/
    public function handleNotFound(callable $handler)
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    protected function calculateResourceName($routeName)
    {

        if (strpos($routeName, '.') === false) {
            return $routeName;
        }

        $tiles = explode('.', $routeName);

        if ( count($tiles) == 2) {
            return $tiles[0];
        }

        array_pop($tiles);

        return implode('.', $tiles);

    }

    protected function getRouteName(Request $request)
    {
        if (!$route = $request->route()) {
            throw new OutOfBoundsException("Request has no route, no chance to get the resource name");
        }
        return $route->getName();
    }

}
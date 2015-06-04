<?php namespace Cmsable\Resource\Contracts;

use Illuminate\Http\Request;

interface Detector
{

    /**
     * Return the resource name which is currently handled by $request
     * It defaults to the first segment of your route name
     *
     * @param Illuminate\Http\Request
     * @return string
     **/
    public function resourceByRequest(Request $request);

    /**
     * Manually map $routeName to $resource
     *
     * @param string $routeName
     * @param string $resource
     * @return self
     **/
    public function mapToRoute($routeName, $resource);

    /**
     * If the $resource could not be found, try it with this callable
     *
     * @param callable $handler
     * @return self
     **/
    public function handleNotFound(callable $handler);

}
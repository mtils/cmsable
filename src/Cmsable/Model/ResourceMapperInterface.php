<?php namespace Cmsable\Model;

use Illuminate\Http\Request;

interface ResourceMapperInterface
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
     * Returns the resource name of model $model
     *
     * @param object|string $model (classname)
     * @return string
     **/
    public function resourceByModel($model);

    /**
     * Return the model class name for resource name $resource
     *
     * @param string $resource name
     * @return string classname of model
     **/
    public function modelByResource($resource);

}
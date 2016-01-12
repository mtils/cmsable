<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class Resource extends Facade
{

    protected static $searchFactory;

    protected static $criteriaBuilder;

    protected static $modelPresenter;

    public static function search($resource, $params=[])
    {
        $modelClass = static::getFacadeRoot()->modelClass($resource);
        $criteria = static::criteriaBuilder()->criteria($modelClass, $params);
        $criteria->setResource($resource);
        $keys = static::modelPresenter()->keys($modelClass);

        return static::searchFactory()->search($criteria)->withKey($keys);
    }

    protected static function searchFactory()
    {
        if (!static::$searchFactory) {
            static::$searchFactory = static::getFacadeApplication()->make('versatile.search-factory');
        }
        return static::$searchFactory;
    }

    protected static function criteriaBuilder()
    {
        if (!static::$criteriaBuilder) {
            static::$criteriaBuilder = static::getFacadeApplication()->make('versatile.criteria-builder');
        }
        return static::$criteriaBuilder;
    }

    protected static function modelPresenter()
    {
        if (!static::$modelPresenter) {
            static::$modelPresenter = static::getFacadeApplication()->make('versatile.model-presenter');
        }
        return static::$modelPresenter;
    }

    protected static function getFacadeAccessor()
    {
        return 'cmsable.resource-distributor';
    }

}
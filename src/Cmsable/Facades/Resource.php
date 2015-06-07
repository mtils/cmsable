<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class Resource extends Facade
{

    protected static function getFacadeAccessor(){
        return 'cmsable.resource-distributor';
    }

}
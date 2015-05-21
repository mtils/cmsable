<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class Pages extends Facade
{

    protected static function getFacadeAccessor(){
        return 'cmsable.pagequery-factory';
    }

}
<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class PageTypes extends Facade{

    protected static function getFacadeAccessor(){
        return 'cmsable.pageTypes';
    }

}
<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class PageType extends Facade{

    protected static function getFacadeAccessor(){
        return 'cmsable.pageTypes';
    }

}
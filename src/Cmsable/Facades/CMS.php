<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class CMS extends Facade{

    protected static function getFacadeAccessor(){
        return 'cmsable.cms';
    }
}
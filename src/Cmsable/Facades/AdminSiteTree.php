<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class AdminSiteTree extends Facade{

    protected static function getFacadeAccessor(){
        return 'adminSiteTree';
    }
}
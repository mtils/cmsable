<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class SiteTree extends Facade{

    protected static function getFacadeAccessor(){
        return 'siteTree';
    }
}
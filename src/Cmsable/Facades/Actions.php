<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class Actions extends Facade{

    protected static function getFacadeAccessor(){
        return 'cmsable.actions';
    }

}
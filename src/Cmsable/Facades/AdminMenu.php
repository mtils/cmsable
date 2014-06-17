<?php namespace Cmsable\Facades;

use Illuminate\Support\Facades\Facade;

class AdminMenu extends Facade{

    protected static function getFacadeAccessor(){
        return 'adminMenu';
    }
}
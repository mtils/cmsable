<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Page classname
    |--------------------------------------------------------------------------
    |
    | This option sets the model class of the sitetree
    |
    |
    */
    'page_model' => 'Cmsable\Model\Page',

    /*
    |--------------------------------------------------------------------------
    | Your User Model
    |--------------------------------------------------------------------------
    |
    | Say cmsable what is your user model
    |
    |
    */
    'user_model' => 'User',

    'default_scope_id' => 1,

    'user_provider' => 'Cmsable\Auth\LaravelCurrentUserProvider',

    'cms-editor-css' => '/css/editor.css',

    'sitetree-controller' => ['routename-prefix' => 'sitetree',
                              'main-template' => 'cmsable::sitetree',
                              'new-page-template' => 'cmsable::sitetree-new'],

    'redirect-controller' => ['routename-prefix' => 'cms-redirect'],
    'menu-filters' => '',

    'breadcrumbs' => [
        'file' => app_path() . '/breadcrumbs.php'
     ]

];
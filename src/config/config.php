<?php

return array(
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

    'pagetype-categories' => array(
        'default'    => array('icon'    => 'fa-file-text-o')
    ),

    'cms-editor-css' => '/css/editor.css',

    'sitetree-controller.main-template' => 'cmsable::sitetree',

    'sitetree-controller.new-page-template' => 'cmsable::sitetree-new'
);
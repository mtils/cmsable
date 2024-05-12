<?php namespace Cmsable\Controller;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Application;
use Route;

trait ControllerRouteTrait{

    public static $crudDefaults = [

        // list resources: GET $path/
        'index'   => [
            'verb' => 'get',
            'path' => ''
         ],

         // show the page (form) to create a resource: GET $path/create
        'create'  => [
            'verb' => 'get',
            'path' => '/create'
        ],

        // create the resource: POST $path/create
        'store'   => [
            'verb' => 'post',
            'path' => '/create'
        ],

        // show a resource: GET $path/show/{id}
        'show'    => [
            'verb' => 'get',
            'path' => '/show/{id}'
        ],

        // show the page (form) to edit a resource: GET $path/edit/{id}
        'edit'    => [
            'verb' => 'get',
            'path' => '/edit/{id}'
        ],

        // update the resource (after submitting edit form): POST $path/edit/{id}
        'update'  => [
            'verb' => 'post',
            'path' => '/edit/{id}'
        ],

        // delete a resource: GET $path/destroy
        'destroy' => [
            'verb' => 'get',
            'path' => '/destroy/{id?}'
        ]
    ];

    public static $treeDefaults = [

        // Show the tree: GET $path/tree
        'tree' => [
            'verb' => 'get',
            'path' => '/tree/{id?}'
        ],

        // Move a node: GET $path/move/{id}
        'move' => [
            'verb' => 'get',
            'path' => '/move/{id}'
        ]

    ];

    /**
     * @brief Creates all routes to edit a resource. Laravel own
     *        Route::resource uses put/patch and delete verbs, which are not
     *        valid HTML in form methods. The method I prefer is to have the
     *        same url for create/store and edit/delete to simply set form
     *        actions
     *
     * @param string $path Your route path
     * @param string $class The controller class (if not passed this)
     * @param string $name A manual passed name
     **/
    public static function crudRoute($path, $class=NULL, $name=NULL){

        $class = ($class === NULL) ? get_called_class() : $class;

        $name = ($name === NULL) ? str_replace('/','.',trim($path,'/')) : $name;

        $path = rtrim($path,'/');

        static::assignRoutes($path, $class, $name, static::$crudDefaults);

    }

    public static function crudTreeRoute($path, $class=NULL, $name=NULL){

        $class = ($class === NULL) ? get_called_class() : $class;

        $name = ($name === NULL) ? str_replace('/','.',trim($path,'/')) : $name;

        $path = rtrim($path,'/');

        $routeData = array_merge(static::$crudDefaults, static::$treeDefaults);

        static::assignRoutes($path, $class, $name, $routeData);

    }

    protected static function assignRoutes($path, $class, $name, array $routeData){

        /** @var Registrar $router */
        $router = Application::getInstance()->make(Registrar::class);
        foreach($routeData as $method=>$data){

            $router->{$data['verb']}($path.$data['path'],[
                'as' => "$name.$method",
                'uses' => "$class@$method"
            ]);

        }

    }

}
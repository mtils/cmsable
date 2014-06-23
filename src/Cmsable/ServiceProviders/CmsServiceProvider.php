<?php namespace Cmsable\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Cmsable\Model\AdjacencyListSiteTreeModel;
use Cmsable\Cms\ControllerDescriptorLoaderManual;
use Cmsable\Html\Menu;
use Cmsable\Html\MenuFilter;
use Cmsable\Html\SiteTreeUrlGenerator;
use Cmsable\Routing\RouterConnector;
use Cmsable\Validators\CmsValidator;
use ConfigurableClass\LaravelConfigModel;
use DB;
use Cmsable\Html\MenuFilterRegistry;

use FormObject\Registry;


class CmsServiceProvider extends ServiceProvider{

    private $cmsPackagePath;

    protected $cmsQualifier = 'ems/cmsable';

    protected $cmsNamespace = 'cmsable';

    public function register(){

//         $this->package('ems/cmsable','cmsable', realpath(__DIR__.'/../../../src'));
        $this->registerPackageConfig();

        $this->app->singleton('pageTypes', function(){
            return new ControllerDescriptorLoaderManual($this->app['events']);
        });

        $this->registerRouterConnector();

        $this->app->singleton('adminMenu', function(){
            return new Menu($this->app['cms']->getTreeModel('admin'));
        });

        $this->app->singleton('ConfigurableClass\ConfigModelInterface', function(){
            $model = $this->app->make('ConfigurableClass\LaravelConfigModel');
            $model->setTableName('controller_config');
            return $model;
        });

        $this->app->singleton('menu', function(){
            return new Menu($this->app['cms']->getTreeModel('default'));
        });

        $this->app['url'] = $this->app->share(function($app)
        {
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();

            return new SiteTreeUrlGenerator($routes, $app->rebinding('request', function($app, $request){
                $app['url']->setRequest($request);
            }));
        });

        $this->app->singleton('Cmsable\Html\MenuFilterRegistry', function($app){
            return new MenuFilterRegistry($app['events']);
        });

        $this->app['events']->listen('cmsable::menu-filter.create.default', function($registry){
            $registry->setFilter('default', new MenuFilter(array('show_in_menu' => 1)));
            return FALSE;
        },$priority=1);

        $this->app['events']->listen('cmsable::menu-filter.create.asidemenu', function($registry){
            $registry->setFilter('asidemenu', new MenuFilter(array('show_in_aside_menu' => 1)));
            return FALSE;
        },$priority=1);
    }

    protected function registerRouterConnector(){

        $this->app->singleton('cms', function($app){

            $pageClass = $app['config']->get('cmsable::page_model');

            // Create SiteTreeModels
            $treeModel =  new AdjacencyListSiteTreeModel($pageClass,1);
            $adminTreeModel = new AdjacencyListSiteTreeModel($pageClass,2);

            $descLoader = new ControllerDescriptorLoaderManual($this->app['events']);

            if($descriptors = $app['config']->get('cmsable::pagetypes')){
                $descLoader->setDescriptors($descriptors);
            }

            $cms = new RouterConnector($descLoader);
            $cms->addCmsRoute('/', $treeModel, 'default');
            $cms->addCmsRoute('/admin', $adminTreeModel, 'admin');
            $cms->register($this->app['router']);

            return $cms;
        });
    }

    public function boot(){

        $this->registerPackageLang();

        $this->app->validator->resolver(function($translator, $data, $rules, $messages){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->addSiteTreeLoader($this->app['cms']->getTreeModel('default'));
            $validator->addSiteTreeLoader($this->app['cms']->getTreeModel('admin'));
            $validator->setRouter($this->app['router']);
            return $validator;
        });
    }

    /**
     * @brief Separatly register package config, because lang would be to early
     *        in register
     **/
    protected function registerPackageConfig(){

        $configPath = $this->getCmsPackagePath().'/config';

        if ($this->app['files']->isDirectory($configPath))
        {
            $this->app['config']->package($this->cmsQualifier, $configPath, $this->cmsNamespace);
        }
    }

    /**
     * @brief Separatly register translator config, because lang would be to early
     *        in register. You get otherwise a "class translator not found" error
     **/
    protected function registerPackageLang(){

        $langPath = $this->getCmsPackagePath().'/lang';

        if ($this->app['files']->isDirectory($langPath))
        {
            $this->app['translator']->addNamespace($this->cmsNamespace, $langPath);
        }
    }

    protected function getCmsPackagePath(){

        if(!$this->cmsPackagePath){
            $this->cmsPackagePath = realpath(__DIR__.'/../../../src');
        }
        return $this->cmsPackagePath;

    }

}
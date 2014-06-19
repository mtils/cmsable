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

use FormObject\Registry;


class CmsServiceProvider extends ServiceProvider{

    public function register(){

        $this->package('ems/cmsable','cmsable', realpath(__DIR__.'/../../../src'));

        $this->app->singleton('pageTypes', function(){
            return new ControllerDescriptorLoaderManual($this->app['events']);
        });

        $this->registerRouterConnector();

        $this->app->singleton('adminMenu', function(){
            return new Menu($this->app['cms']->getTreeModel('admin'),
                            new MenuFilter());
        });

        $this->app->singleton('ConfigurableClass\ConfigModelInterface', function(){
            $model = $this->app->make('ConfigurableClass\LaravelConfigModel');
            $model->setTableName('controller_config');
            return $model;
        });

        $this->app->singleton('menu', function(){
            return new Menu($this->app['cms']->getTreeModel('default'),
                            new MenuFilter());
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
        $this->app->validator->resolver(function($translator, $data, $rules, $messages){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->addSiteTreeLoader($this->app['cms']->getTreeModel('default'));
            $validator->addSiteTreeLoader($this->app['cms']->getTreeModel('admin'));
            $validator->setRouter($this->app['router']);
            return $validator;
        });
    }
}
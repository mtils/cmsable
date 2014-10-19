<?php namespace Cmsable\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Cmsable\Model\AdjacencyListSiteTreeModel;
use Cmsable\Cms\PageType;
use Cmsable\Cms\ManualPageTypeRepository;
use Cmsable\Html\Menu;
use Cmsable\Html\SiteTreeUrlGenerator;
use Cmsable\Routing\RouterConnector;
use Cmsable\Validators\CmsValidator;
use ConfigurableClass\LaravelConfigModel;
use DB;
use Cmsable\Html\MenuFilterRegistry;
use Cmsable\Cms\Action\Registry as ActionRegistry;

use Cmsable\Routing\Routable\CreatorRegistry;
use Cmsable\Routing\Routable\PathEqualsCreator;
use Cmsable\Routing\Routable\ControllerMethodCreator;
use Cmsable\Routing\Routable\SubRoutableCreator;
use Cmsable\Routing\SiteTreeRoute;
use Cmsable\Controller\SiteTreeController;
use Cmsable\Controller\AdminSiteTreeController;

use FormObject\Registry;


class CmsServiceProvider extends ServiceProvider{

    private $cmsPackagePath;

    protected $cmsQualifier = 'ems/cmsable';

    protected $cmsNamespace = 'cmsable';

    protected $currentUserProvider;

    protected $defaultScopeId = 1;

    protected $adminScopeId = 2;

    public function register(){

        $this->registerPackageConfig();

        $serviceProvider = $this;

        $this->registerPageTypeRepository();

        $this->registerActionRegistry();

        $this->registerRoutableCreator();

        $this->registerRouterConnector();

        $this->registerDefaultTreeModel();

        $this->registerAdminTreeModel();

        $this->app->singleton('adminMenu', function($app){
            return new Menu($app['cmsable.tree-admin'], $app['cmsable.route-admin']);
        });

        $this->app->singleton('ConfigurableClass\ConfigModelInterface', function(){
            $model = $this->app->make('ConfigurableClass\LaravelConfigModel');
            $model->setTableName('controller_config');
            return $model;
        });

        $this->app->singleton('menu', function($app){
            return new Menu($app['cmsable.tree-default'], $app['cmsable.route-default']);
        });

        $this->registerSiteTreeController();

        $this->registerAdminSiteTreeController();

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

        $this->createMenuFilters();

    }

    protected function registerPageTypeRepository(){
        $this->app->singleton('cmsable.pageTypes', function($app){
            return new ManualPageTypeRepository(new PageType, $app, $app['events']);
        });
    }

    protected function registerActionRegistry(){

        $this->app->singleton('cmsable.actions', function(){
            return new ActionRegistry();
        });

    }

    protected function registerRoutableCreator(){

        $serviceProvider = $this;

        $this->app->singleton('cmsable.routable-creator', function($app) use ($serviceProvider){

            $registry = new CreatorRegistry;
            $serviceProvider->registerRoutableCreators($registry);
            return $registry;

        });

    }

    protected function registerRoutableCreators(CreatorRegistry $registry){
        
        $this->registerPathEqualsCreator($registry);
        $this->registerControllerMethodCreator($registry);
        $this->registerSubRoutableCreator($registry);

    }

    protected function registerDefaultTreeModel(){

        $pageClass = $this->app['config']->get('cmsable::page_model');

        $this->app->singleton('cmsable.tree-default', function($app) use ($pageClass){

            return new AdjacencyListSiteTreeModel($pageClass,1);

        });

    }

    protected function registerAdminTreeModel(){

        $pageClass = $this->app['config']->get('cmsable::page_model');

        $this->app->singleton('cmsable.tree-admin', function($app) use ($pageClass){

            return new AdjacencyListSiteTreeModel($pageClass,2);

        });

    }

    protected function registerPathEqualsCreator(CreatorRegistry $registry){

        $pageTypes = $this->app->make('cmsable.pageTypes');
        $inspector = $this->app->make('Illuminate\Routing\ControllerInspector');
        $registry->addCreator(new PathEqualsCreator($pageTypes, $inspector));

    }

    protected function registerControllerMethodCreator(CreatorRegistry $registry){

        $pageTypes = $this->app->make('cmsable.pageTypes');
        $inspector = $this->app->make('Illuminate\Routing\ControllerInspector');
        $registry->addCreator(new ControllerMethodCreator($pageTypes, $inspector));

    }

    protected function registerSubRoutableCreator(CreatorRegistry $registry){

        $pageTypes = $this->app->make('cmsable.pageTypes');
        $inspector = $this->app->make('Illuminate\Routing\ControllerInspector');
        $registry->addCreator(new SubRoutableCreator($pageTypes, $inspector));

    }

    protected function registerRouterConnector(){

        $serviceProvider = $this;

        $this->app->singleton('cms', function($app) use ($serviceProvider){

            $pageClass = $app['config']->get('cmsable::page_model');

            // Create SiteTreeModels
            $treeModel =  new AdjacencyListSiteTreeModel($pageClass,1);
            $adminTreeModel = new AdjacencyListSiteTreeModel($pageClass,2);

            $pageTypeLoader = $this->app->make('cmsable.pageTypes');

            if($pageTypeArray = $app['config']->get('cmsable::pagetypes')){
                $pageTypeLoader->fillByArray($pageTypeArray);
            }

            $cms = new RouterConnector($pageTypeLoader, $this->getCurrentUserProvider());

            $serviceProvider->addDefaultSiteTreeRoute($cms);
            $serviceProvider->addAdminSiteTreeRoute($cms);

            $cms->register($app['router']);

            return $cms;
        });
    }

    protected function addDefaultSiteTreeRoute(RouterConnector $cms){

        $route = new SiteTreeRoute($this->app->make('cmsable.tree-default'),
                                   $this->app->make('cmsable.routable-creator'),
                                   '/');

        $cms->addCmsRoute($route,'default');

        $this->app->instance('cmsable.route-default', $route);

    }

    protected function addAdminSiteTreeRoute(RouterConnector $cms){

        $route = new SiteTreeRoute($this->app->make('cmsable.tree-admin'),
                                   $this->app->make('cmsable.routable-creator'),
                                   '/admin');
        $cms->addCmsRoute($route,'admin');

        $this->app->instance('cmsable.route-admin', $route);

    }

    protected function getCurrentUserProvider(){
        if(!$this->currentUserProvider){
            $userModel = $this->app['config']->get('cmsable::user_model');
            $providerClass = $this->app['config']->get('cmsable::user_provider');
            $this->currentUserProvider = $this->app->make($providerClass, array($userModel));
        }
        return $this->currentUserProvider;

    }

    protected function createMenuFilters(){

        $this->app->singleton('Cmsable\Html\MenuFilterRegistry', function($app){
            return new MenuFilterRegistry($app['events']);
        });

        if($filterConfig = $this->app['config']->get('cmsable::menu-filters')){
            $filterPath = app_path().'/'.ltrim($filterConfig,'/');
            if(is_string($filterConfig) && file_exists($filterPath)){
                include $filterPath;
            }
            return;
        }

        $this->app['events']->listen('cmsable::menu-filter.create.default', function($filter){

            $filter->add('show_in_menu',function($page){
                return (bool)$page->show_in_menu;
            });

        },$priority=1);

        $this->app['events']->listen('cmsable::menu-filter.create.asidemenu', function($filter){

            $filter->add('show_in_aside_menu',function($page){
                return (bool)$page->show_in_aside_menu;
            });

        },$priority=1);

        $provider = $this->getCurrentUserProvider();

        $this->app['events']->listen('cmsable::menu-filter.create.*', function($filter) use ($provider){

            $filter->add('auth',function($page) use ($provider){
                return $page->isAllowed('view', $provider->current());
            });

        },$priority=1);

    }

    protected function registerSiteTreeController(){
        $this->app->bind('\Cmsable\Controller\SiteTreeController',function($app){
            return new SiteTreeController($app['PageForm'], $app['cmsable.tree-default']);
        });
    }

    protected function registerAdminSiteTreeController(){
        $this->app->bind('\Cmsable\Controller\AdminSiteTreeController',function($app){
            return new AdminSiteTreeController($app['Cmsable\Form\AdminPageForm'], $app['cmsable.tree-admin']);
        });
    }

    public function boot(){

        $this->registerPackageLang();

        $app = $this->app;

        $this->app->make('cms');

        $this->app->validator->resolver(function($translator, $data, $rules, $messages) use ($app){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->addSiteTreeLoader($app['cmsable.tree-default']);
            $validator->addSiteTreeLoader($app['cmsable.tree-admin']);
            $validator->setRouter($app['router']);
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
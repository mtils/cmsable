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
use Cmsable\Cms\Action\NamedGroupCreator;
use Cmsable\Cms\Action\ClassResourceTypeIdentifier;

use Cmsable\Controller\SiteTreeController;
use Cmsable\Controller\AdminSiteTreeController;
use Cmsable\Http\SiteTreeModelPathCreator;
use Cmsable\Cms\Application;
use Cmsable\Routing\ControllerDispatcher;
use Cmsable\Routing\SiteTreePathFinder;
use Cmsable\Routing\SiteTreeUrlDispatcher;
use Cmsable\Html\Breadcrumbs\SiteTreeCrumbsCreator;
use Cmsable\Html\Breadcrumbs\Factory as BreadcrumbFactory;
use Blade;
use Log;

class CmsServiceProvider extends ServiceProvider{

    private $cmsPackagePath;

    protected $cmsQualifier = 'ems/cmsable';

    protected $cmsNamespace = 'cmsable';

    protected $defaultScopeId = 1;

    protected $adminScopeId = 2;

    public function register(){

        $this->registerPackageConfig();

        $serviceProvider = $this;

        $this->registerPageTypeRepository();

        $this->registerCmsApplication();

        $this->registerDefaultTreeModel();

        $this->registerAdminTreeModel();

        $this->registerUserProvider();

        $this->registerActionRegistry();

        $this->app->singleton('ConfigurableClass\ConfigModelInterface', function(){
            $model = $this->app->make('ConfigurableClass\LaravelConfigModel');
            $model->setTableName('controller_config');
            return $model;
        });

        $this->registerSiteTreeController();

        $this->registerRedirctController();

        $this->app['url'] = $this->app->share(function($app)
        {

            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();

            $urlGenerator = new SiteTreeUrlDispatcher($routes, $app->rebinding('request', function($app, $request){
                $app['url']->setRequest($request);

            }));

            $urlGenerator->setCurrentScopeProvider($app['cmsable.cms']);

            return $urlGenerator;
        });

        $this->createMenuFilters();

        Blade::extend(function($view, $compiler){
            $pattern = $compiler->createMatcher('toJsTree');
            return preg_replace($pattern, '$1<?php $f = new \BeeTree\Support\HtmlPrinter(); echo $f->toJsTree$2 ?>', $view);
        });

    }

    protected function registerPageTypeRepository(){

        $serviceProvider = $this;

        $this->app->singleton('cmsable.pageTypes', function($app){
            return new ManualPageTypeRepository(new PageType, $app, $app['events']);
        });

        $this->app['events']->listen('cmsable.pageTypeLoadRequested', function($pageTypes) use ($serviceProvider){
            $serviceProvider->fillPageTypeRepository($pageTypes);
        });
    }

    protected function registerCmsApplication(){

        $this->app->middleWare('Cmsable\Http\CmsRequestInjector');

        $this->app->singleton('cmsable.cms', function($app){

            $cmsApp = new Application($app->make('cmsable.pageTypes'), $app['events']);
            $cmsApp->setEventDispatcher($app['events']);
            return $cmsApp;

        });

        $app = $this->app;

        if($app['config']['app.debug']){
            $app['events']->listen('cmsable::cms-path-setted', function($cmsPath){
                Log::debug($cmsPath->getOriginalPath() . ' => ' . $cmsPath->getRewrittenPath());
            });
        }

        $this->app['events']->listen('cmsable::request.path-requested', function($request) use ($app){
            $app->make('cmsable.cms')->attachCmsPath($request);
        });

        $serviceProvider = $this;

        $this->app['events']->listen('cmsable::path-creators-requested', function($cms) use ($serviceProvider){
            $serviceProvider->addPathCreators($cms);
        });

    }

    protected function fillPageTypeRepository($pageTypeLoader){

        if($pageTypeArray = $this->app['config']->get('cmsable::pagetypes')){
            $pageTypeLoader->fillByArray($pageTypeArray);
        }

    }

    protected function registerActionRegistry(){

        $this->app->singleton('cmsable.actions', function($app){
            return new ActionRegistry(
                new NamedGroupCreator,
                new ClassResourceTypeIdentifier,
                $app->make('Cmsable\Auth\CurrentUserProviderInterface')
            );
        });

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

    public function addPathCreators(Application $cmsApplication){
        $this->addDefaultPathCreator($cmsApplication);
        $this->addAdminPathCreator($cmsApplication);
    }

    protected function addDefaultPathCreator(Application $cmsApplication){

        $pathCreator = new SiteTreeModelPathCreator(
            $this->app->make('cmsable.tree-default'),
            $this->app->make('cmsable.pageTypes'),
            '/'
        );

        $this->app->instance('cmsable.default-path-creator', $pathCreator);

        $cmsApplication->addPathCreator($pathCreator);

    }

    protected function addAdminPathCreator(Application $cmsApplication){

        $pathCreator = new SiteTreeModelPathCreator(
            $this->app->make('cmsable.tree-admin'),
            $this->app->make('cmsable.pageTypes'),
            '/admin'
        );

        $this->app->instance('cmsable.admin-path-creator', $pathCreator);

        $cmsApplication->addPathCreator($pathCreator);

    }

    protected function registerUserProvider(){

        $this->app->singleton('Cmsable\Auth\CurrentUserProviderInterface', function($app){

            $userModel = $app['config']->get('cmsable::user_model');
            $providerClass = $app['config']->get('cmsable::user_provider');
            return $app->make($providerClass, [$userModel]);

        });

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

        $provider = $this->app->make('Cmsable\Auth\CurrentUserProviderInterface');

        $this->app['events']->listen('cmsable::menu-filter.create.*', function($filter) use ($provider){

            $filter->add('auth',function($page) use ($provider){
                return $page->isAllowed('view', $provider->current());
            });

        },$priority=1);

         $this->app['events']->listen('cmsable::menu-filter.create.*', function($filter){

            $filter->add('page_exists',function($page){
                return (bool)$page->exists;
            });

        },$priority=1);

    }

    protected function registerSiteTreeController(){

        $routePrefix = $this->app['config']->get('cmsable::sitetree-controller.routename-prefix');

        $this->app['router']->get(
            "$routePrefix/new",
            ['as'=>"$routePrefix-new",'uses'=>'Cmsable\Controller\SiteTreeController@getNew']
        );

        $this->app['router']->get(
            "$routePrefix/create",
            ['as'=>"$routePrefix-make",'uses'=>'Cmsable\Controller\SiteTreeController@getCreate']
        );

        $this->app['router']->post(
            "$routePrefix/create",
            ['as'=>"$routePrefix-create",'uses'=>'Cmsable\Controller\SiteTreeController@postCreate']
        );

        $this->app['router']->get(
            "$routePrefix/edit/{id}",
            ['as'=>"$routePrefix-edit",'uses'=>'Cmsable\Controller\SiteTreeController@getEdit']
        );

        $this->app['router']->post(
            "$routePrefix/edit/{id}",
            ['as'=>"$routePrefix-store",'uses'=>'Cmsable\Controller\SiteTreeController@postEdit']
        );

        $this->app['router']->get(
            "$routePrefix/delete/{id}",
            ['as'=>"$routePrefix-delete",'uses'=>'Cmsable\Controller\SiteTreeController@getDelete']
        );

        $this->app['router']->get(
            "$routePrefix/move/{id}",
            ['as'=>"$routePrefix-move",'uses'=>'Cmsable\Controller\SiteTreeController@getMove']
        );

        $this->app['router']->get(
            "$routePrefix/js-config",
            ['as'=>"$routePrefix-jsconfig",'uses'=>'Cmsable\Controller\SiteTreeController@getJsConfig']
        );

        $this->app['router']->get(
            "$routePrefix",
            ['as'=>"$routePrefix",'uses'=>'Cmsable\Controller\SiteTreeController@getIndex']
        );

        $this->app->bind('Cmsable\Controller\SiteTreeController',function($app){

            $treeModel = $app['cmsable.tree-default'];
            $scope = 'default';

            if($cmsPath = $app['cmsable.cms']->getCurrentCmsPath()){
                if($node = $cmsPath->getMatchedNode()){
                    if($node->getPageTypeId() == 'cmsable.admin-sitetree-editor'){
                        $treeModel = $app['cmsable.tree-admin'];
                        $scope = 'admin';
                    }
                }
            }

            $c = new SiteTreeController($app['PageForm'], $treeModel);
            $c->setRouteScope($scope);
            return $c;

        });
    }

    protected function registerRedirctController(){

        $routePrefix = $this->app['config']->get('cmsable::redirect-controller.routename-prefix');

        $this->app['router']->get(
            "$routePrefix",
            ['as'=>"$routePrefix",'uses'=>'Cmsable\Controller\RedirectorController@index']
        );

    }

    public function boot(){

        $this->registerPackageLang();

        $this->injectControllerDispatcher();

        $this->registerDefaultUrlGenerator();

        $this->registerAdminUrlGenerator();

        $app = $this->app;

        $this->app->validator->resolver(function($translator, $data, $rules, $messages) use ($app){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->addSiteTreeLoader($app['cmsable.tree-default']);
            $validator->addSiteTreeLoader($app['cmsable.tree-admin']);
            $validator->setRouter($app['router']);
            return $validator;
        });

        $this->registerBreadcrumbs();
        $this->registerMenu();

    }

    protected function registerDefaultUrlGenerator(){

        $pathFinder = new SiteTreePathFinder($this->app['cmsable.tree-default'],
                                             $this->app['cmsable.cms']);

        $routes = $this->app['router']->getRoutes();

        $urlGenerator = new SiteTreeUrlGenerator($routes, $this->app['request']);

        $urlGenerator->setPathFinder($pathFinder);


        $this->app['url']->setForwarder('default', $urlGenerator);

    }

    protected function registerAdminUrlGenerator(){

        $pathFinder = new SiteTreePathFinder($this->app['cmsable.tree-admin'],
                                             $this->app['cmsable.cms']);
        $pathFinder->routeScope = 'admin';

        $routes = $this->app['router']->getRoutes();

        $urlGenerator = new SiteTreeUrlGenerator($routes,
                                                 $this->app['request']);

        $urlGenerator->setPathFinder($pathFinder);

        $this->app['url']->setForwarder('admin', $urlGenerator);



    }

    protected function injectControllerDispatcher(){

        $dispatcher = new ControllerDispatcher($this->app['router'], $this->app);
        $this->app['router']->setControllerDispatcher($dispatcher);
        $this->app['cmsable.cms']->setControllerDispatcher($dispatcher);

        $cmsApp = $this->app['cmsable.cms'];

        $this->app['router']->filter('cmsable.scope-filter', function($route, $request) use ($cmsApp){
            return $cmsApp->onRouterBefore($route, $request);
        });

    }

    protected function registerBreadcrumbs(){

        $serviceProvider = $this;

        $this->app->singleton('cmsable.breadcrumbs', function($app) use ($serviceProvider){

            $crumbsCreator = new SiteTreeCrumbsCreator($app['cmsable.tree-default'],
                                                       $app['cmsable.cms']);

            $factory = new BreadcrumbFactory($crumbsCreator, $app['router']);
            $factory->setEventDispatcher($app['events']);

            return $factory;

        });

        $this->app['events']->listen('cmsable::breadcrumbs-load', function($factory) use ($serviceProvider){
            $serviceProvider->loadBreadCrumbRules($factory);
        });

    }

    protected function loadBreadCrumbRules($factory){

        $filePath = $this->app['config']->get('cmsable::breadcrumbs.file');

        if(file_exists($filePath)){
            include_once $filePath;
        }

        $this->appendBreadcrumbsForSiteTreeControllers($factory);
    }

    protected function appendBreadcrumbsForSiteTreeControllers($factory){

        $routePrefix = $this->app['config']->get('cmsable::sitetree-controller.routename-prefix');

        $app = $this->app;

        $factory->register("$routePrefix-new", function($breadcrumbs) use ($app){

            $menuTitle = $app['translator']->get('cmsable::pages.sitetree-new.menu_title');
            $title = $app['translator']->get('cmsable::pages.sitetree-new.title');

            $breadcrumbs->add($menuTitle, null, $title);

        });

        $factory->register("$routePrefix-make", function($breadcrumbs) use ($app){

            $menuTitle = $app['translator']->get('cmsable::pages.sitetree-new.menu_title');
            $title = $app['translator']->get('cmsable::pages.sitetree-new.title');

            $breadcrumbs->add($menuTitle, null, $title);

        });

        $factory->register("$routePrefix-edit", function($breadcrumbs, $siteId) use ($app){

            $page = null;

            if(!$page = $app['cmsable.tree-default']->pageById($siteId)){
                $page = $app['cmsable.tree-admin']->pageById($siteId);
            }

            if(!$page){
                return;
            }

            $reversedCrumbs = [$page];

            while($parent = $page->parentNode()){
                if(!$parent->isRootNode()){
                    $reversedCrumbs[] = $parent;
                }
                $page = $parent;
            }

            foreach(array_reverse($reversedCrumbs) as $crumb){
                $breadcrumbs->add($crumb->menu_title,
                                  $app['url']->action('edit',[$crumb->id]),
                                  $crumb->title,
                                  $crumb->content);
            }

        });

    }

    protected function registerMenu(){

        $this->app->singleton('menu', function($app){

            $menu = new Menu($app['cmsable.cms'], $app['cmsable.breadcrumbs']);
            $menu->setCurrentScopeProvider($app['cmsable.cms']);

            $menu->setForwarder('default', $app['cmsable.tree-default']);
            $menu->setForwarder('admin', $app['cmsable.tree-admin']);

            return $menu;
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
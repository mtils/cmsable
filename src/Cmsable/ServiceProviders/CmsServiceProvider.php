<?php namespace Cmsable\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
use Cmsable\Model\AdjacencyListSiteTreeModel;
use Cmsable\PageType\PageType;
use Cmsable\PageType\ManualRepository;
use Cmsable\Html\Menu;
use Cmsable\Html\SiteTreeUrlGenerator;
use Cmsable\Validators\CmsValidator;
use DB;
use Cmsable\Html\MenuFilterRegistry;
use Cmsable\Cms\Action\Registry as ActionRegistry;
use Cmsable\Cms\Action\NamedGroupCreator;
use Cmsable\Cms\Action\ClassResourceTypeIdentifier;

use Cmsable\Controller\SiteTree\SiteTreeController;
use Cmsable\Http\SiteTreeModelPathCreator;
use Cmsable\Cms\Application;
use Cmsable\Routing\ControllerDispatcher;
use Cmsable\Routing\SiteTreePathFinder;
use Cmsable\Routing\SiteTreeUrlDispatcher;
use Cmsable\Html\Breadcrumbs\SiteTreeCrumbsCreator;
use Cmsable\Html\Breadcrumbs\Factory as BreadcrumbFactory;
use Cmsable\Controller\SiteTree\Plugin\Dispatcher;
use Cmsable\View\FallbackFileViewFinder;
use Cmsable\PageType\DBConfigRepository;
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

//         $serviceProvider = $this;

        $this->registerSiteTreeModel();

        $this->registerTreeModelManager();

        $this->registerTreeScopeRepository();

        $this->registerTreeScopeDetector();

        $this->registerPathCreator();

        $this->injectControllerDispatcher();

        $this->registerTreeScopeProvider();

        $this->registerPageTypeRepository();

        $this->registerConfigTypeRepository();

        $this->registerPageTypeConfigRepository();

        $this->registerPageTypeManager();

        $this->registerCmsApplication();

        $this->registerUserProvider();

        $this->registerActionRegistry();

        $this->app->instance('url.original', $this->app['url']);

        $this->app['url'] = $this->app->share(function($app)
        {

            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $routes = $app['router']->getRoutes();

            $urlGenerator = new SiteTreeUrlGenerator($routes, $app->rebinding('request', function($app, $request){
                $app['url']->setRequest($request);

            }));

            $urlGenerator->setTreeModelManager($app->make('Cmsable\Model\TreeModelManagerInterface'));
            $urlGenerator->setTreeScopeRepository($app->make('Cmsable\Routing\TreeScope\RepositoryInterface'));
            $urlGenerator->setOriginalUrlGenerator($app['url.original']);
            $urlGenerator->setCurrentCmsPathProvider($app->make('Cmsable\Http\CurrentCmsPathProviderInterface'));
            $urlGenerator->setRouter($app['router']);

            return $urlGenerator;
        });

        $this->createMenuFilters();

        $this->registerFileViewFinder();

        Blade::extend(function($view, $compiler){
            $pattern = $compiler->createMatcher('toJsTree');
            return preg_replace($pattern, '$1<?php $f = new \BeeTree\Support\HtmlPrinter(); echo $f->toJsTree$2 ?>', $view);
        });

    }

    protected function registerSiteTreeModel(){

        $pageClass = $this->app['config']->get('cmsable.page_model');

        $this->app->bind('Cmsable\Model\SiteTreeModelInterface', function($app) use ($pageClass){

            return $app->make('Cmsable\Model\AdjacencyListSiteTreeModel',[$pageClass]);

        });

    }

    protected function registerTreeModelManager(){

        $this->app->singleton('Cmsable\Model\TreeModelManagerInterface', function($app){

            return $app->make('Cmsable\Model\TreeModelManager');

        });

    }

    protected function registerTreeScopeRepository(){

        $this->app->singleton('Cmsable\Routing\TreeScope\RepositoryInterface', function($app){

            return $app->make('Cmsable\Routing\TreeScope\RootNodeRepository');

        });

    }

    protected function registerTreeScopeDetector(){

        $this->app->singleton('Cmsable\Routing\TreeScope\DetectorInterface', function($app){

            return $app->make('Cmsable\Routing\TreeScope\PathPrefixDetector');

        });

    }

    protected function registerPathCreator(){

        $this->app->bind('Cmsable\Http\CmsPathCreatorInterface', function($app){

            return $app->make('Cmsable\Http\SiteTreeModelPathCreator');

        });

    }

    protected function injectControllerDispatcher(){

        $this->app->singleton('illuminate.route.dispatcher', function($app)
        {
            return new ControllerDispatcher($app['router'], $app);
        });

    }

    protected function registerTreeScopeProvider(){
        $this->app->singleton('Cmsable\Routing\TreeScope\CurrentTreeScopeProviderInterface', function($app){
            return $app->make('Cmsable\Routing\TreeScope\ConfigTreeScopeProvider');
        });
    }

    protected function registerPageTypeRepository(){

        $serviceProvider = $this;

        $this->app->singleton('Cmsable\PageType\RepositoryInterface', function($app){
            return new ManualRepository($app, $app['events']);
        });

        $this->app['events']->listen('cmsable.pageTypeLoadRequested', function($pageTypes) use ($serviceProvider){
            $serviceProvider->fillPageTypeRepository($pageTypes);
        });
    }

    protected function registerConfigTypeRepository(){

        $this->app->singleton('Cmsable\PageType\ConfigTypeRepositoryInterface', function($app){
            return $app->make('Cmsable\PageType\TemplateConfigTypeRepository');
        });

    }

    protected function registerPageTypeConfigRepository(){

        $this->app->singleton('Cmsable\PageType\ConfigRepositoryInterface', function($app){

            DBConfigRepository::setConnectionResolver($app['db']);

            return $app->make('Cmsable\PageType\DBConfigRepository');

        });

    }

    protected function registerPageTypeManager(){

        $this->app->singleton('cmsable.pageTypes', function($app){

            $manager = $app->make('Cmsable\PageType\Manager');

            return $manager;

        });

    }

    protected function registerCmsApplication(){

        $cmsApp = new Application(
            $this->app->make('Cmsable\Http\CmsPathCreatorInterface'),
            $this->app['events']
        );

        $this->app['router']->filter('cmsable.scope-filter', function($route, $request) use ($cmsApp){
            return $cmsApp->onRouterBefore($route, $request);
        });

       $cmsApp->setControllerDispatcher($this->app['illuminate.route.dispatcher']);

       $this->app->instance('cmsable.cms', $cmsApp);
       $this->app->instance('Cmsable\Cms\Application', $cmsApp);

       if(!$this->app->runningInConsole()) {
           $this->app['Illuminate\Contracts\Http\Kernel']->prependMiddleWare(
                'Cmsable\Http\CmsRequestInjector'
           );
       }
//         $this->app->middleWare('Cmsable\Http\CmsRequestInjector',[$cmsApp]);

//         $this->app->singleton('cmsable.cms', function($app){
// 
//             $cmsApp = new Application(
//                 $app->make('Cmsable\Http\CmsPathCreatorInterface'),
//                 $app['events']
//             );
// 
//             $app['router']->filter('cmsable.scope-filter', function($route, $request) use ($cmsApp){
//                 return $cmsApp->onRouterBefore($route, $request);
//             });
// 
//             $cmsApp->setControllerDispatcher($app['router']->getControllerDispatcher());
// 
//             return $cmsApp;
// 
//         });

        $this->app->instance('Cmsable\Http\CurrentCmsPathProviderInterface', $cmsApp);

        $app = $this->app;

        if ($app->isLocal()) {
            $app['events']->listen('cmsable::cms-path-setted', function($cmsPath){
                Log::debug($cmsPath->getOriginalPath() . ' => ' . $cmsPath->getRewrittenPath());
            });
        }

        $this->app['events']->listen('cmsable::request.path-requested', function($request) use ($app){
            $app->make('cmsable.cms')->attachCmsPath($request);
        });

        $serviceProvider = $this;

    }

    protected function fillPageTypeRepository($pageTypeLoader){

        if($pageTypeArray = $this->app['config']->get('pagetypes')){

            $pageTypeLoader->fillByArray($pageTypeArray);

            $configRepo = $this->app->make('Cmsable\PageType\ConfigTypeRepositoryInterface');

            $configRepo->fillByArray($pageTypeArray,'id','configTemplate');
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

    protected function registerUserProvider(){

        $this->app->singleton('Cmsable\Auth\CurrentUserProviderInterface', function($app){

            $userModel = $app['config']->get('cmsable.user_model');
            $providerClass = $app['config']->get('cmsable.user_provider');
            return $app->make($providerClass, [$userModel]);

        });

    }

    protected function createMenuFilters(){

        $this->app->singleton('Cmsable\Html\MenuFilterRegistry', function($app){
            return new MenuFilterRegistry($app['events']);
        });

        if($filterConfig = $this->app['config']->get('cmsable.menu-filters')){
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

    protected function registerRedirectController(){

        $routePrefix = $this->app['config']->get('cmsable.redirect-controller.routename-prefix');

        $this->app['router']->get(
            "$routePrefix",
            ['as'=>"$routePrefix",'uses'=>'Cmsable\Controller\RedirectorController@index']
        );

    }

    public function boot(){

        $this->registerPackageLang();

        $this->registerSiteTreeControllerRoute();

        $this->registerRedirectController();

        $app = $this->app;

        $this->app->validator->resolver(function($translator, $data, $rules, $messages) use ($app){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->setRouter($app['router']);
            return $validator;
        });

        $this->registerBreadcrumbs();
        $this->registerMenu();


    }

    protected function registerBreadcrumbs(){

        $serviceProvider = $this;

        $this->app->singleton('cmsable.breadcrumbs', function($app) use ($serviceProvider){

            $crumbsCreator = new SiteTreeCrumbsCreator($app['Cmsable\Model\SiteTreeModelInterface'],
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

        $filePath = $this->app['config']->get('cmsable.breadcrumbs.file');

        if(file_exists($filePath)){
            include_once $filePath;
        }

        $this->appendBreadcrumbsForSiteTreeControllers($factory);
    }

    protected function appendBreadcrumbsForSiteTreeControllers($factory){

        $routePrefix = $this->app['config']->get('cmsable.sitetree-controller.routename-prefix');

        $app = $this->app;

        $factory->register("$routePrefix.create", function($breadcrumbs) use ($app){

            $menuTitle = $app['translator']->get('cmsable::pages.sitetree-new.menu_title');
            $title = $app['translator']->get('cmsable::pages.sitetree-new.title');

            $breadcrumbs->add($menuTitle, null, $title);

        });

        $factory->register("$routePrefix.edit", function($breadcrumbs, $siteId) use ($app){

            $page = null;

            $pageModel = $app['Cmsable\Model\SiteTreeModelInterface']->makeNode();

            if(!$editedPage = $pageModel->find($siteId)){
                return;
            }

            $scopeManager = $app['Cmsable\Routing\TreeScope\RepositoryInterface'];

            $scope = $scopeManager->getByModelRootId($editedPage->{$editedPage->rootIdColumn});

            $treeModel = $app['Cmsable\Model\TreeModelManagerInterface']->get($scope);

            if(!$page = $treeModel->pageById($siteId)){
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

            $menu = new Menu(
                $app['cmsable.cms'],
                $app['cmsable.breadcrumbs'],
                $app['Cmsable\Model\TreeModelManagerInterface']
            );

            return $menu;
        });

    }

    protected function registerSiteTreeControllerRoute(){

        $routePrefix = $this->app['config']->get('cmsable.sitetree-controller.routename-prefix');

        $this->app['router']->get(
            "$routePrefix/move/{id}",
            ['as'=>"$routePrefix.move",'uses'=>'Cmsable\Controller\SiteTree\SiteTreeController@move']
        );

        $this->app['router']->get(
            "$routePrefix/js-config",
            ['as'=>"$routePrefix.jsconfig",'uses'=>'Cmsable\Controller\SiteTree\SiteTreeController@getJsConfig']
        );

        $this->app['router']->resource("$routePrefix",'Cmsable\Controller\SiteTree\SiteTreeController');

    }

    protected function registerPluginDispatcher(){

        $dispatcher = new Dispatcher($this->app['Cmsable\PageType\RepositoryInterface'],
                                     $this->app['events'],
                                     $this->app);

    }

    protected function registerFileViewFinder(){

        $oldFinder = $this->app['view.finder'];

        $this->app->bindShared('view.finder', function($app) use ($oldFinder)
        {
            return FallbackFileViewFinder::fromOther($oldFinder);

        });

    }

    /**
     * @brief Separatly register package config, because lang would be to early
     *        in register
     **/
    protected function registerPackageConfig(){

        $configPath = $this->getCmsPackagePath().'/config';

        $configFiles = [
            'cmsable.php',
            'pagetype-categories.php',
            'pagetypes.php'
        ];

        $publishes = [];

        foreach ($configFiles as $configFile) {
            $publishes["$configPath/$configFile"] = config_path($configFile);
        }

        $this->publishes($publishes);


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
            $this->cmsPackagePath = realpath(__DIR__.'/../../..');
        }
        return $this->cmsPackagePath;

    }

}
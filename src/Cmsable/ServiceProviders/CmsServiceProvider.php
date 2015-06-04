<?php namespace Cmsable\ServiceProviders;

use Signal\Support\Laravel\IlluminateBus;
use Cmsable\Lang\OptionalTranslator;
use Versatile\Attributes\BitMaskAttribute;
use Versatile\Attributes\Dispatcher as AttributeDispatcher;
use FormObject\RequestCaster\FlagsToIntCaster;
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
use FormObject\Form;

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
use Cmsable\Routing\TreeScope\TreeScope;
use Cmsable\Resource\Contracts\ReceivesResourceMapper;
use Cmsable\Resource\Contracts\ResourceForm;
use Cmsable\Http\Contracts\DecoratesRequest;
use Cmsable\Resource\Distributor;
use Blade;
use Log;

class CmsServiceProvider extends ServiceProvider{

    private $cmsPackagePath;

    protected $cmsQualifier = 'ems/cmsable';

    protected $cmsNamespace = 'cmsable';

    protected $pageClass;

    protected $pageModel;

    protected $defaultScopeId = 1;

    protected $adminScopeId = 2;

    protected $adminTreeModelFile;

    protected $adminArrayScope;

    protected $adminViewPath;

    public function register(){

        $this->registerPackageConfig();

//         $serviceProvider = $this;

        $this->registerSiteTreeModel();

        $this->registerTreeModelManager();

        $this->registerTreeScopeRepository();

        $this->registerTreeScopeDetector();

        $this->registerPathCreator();

//         $this->injectControllerDispatcher();

        $this->registerTreeScopeProvider();

        $this->registerPageTypeRepository();

        $this->registerConfigTypeRepository();

        $this->registerPageTypeConfigRepository();

        $this->registerPageTypeManager();

        $this->registerCmsApplication();

        $this->registerUserProvider();

        $this->registerActionRegistry();

        $this->registerUrlGenerator();

        $this->createMenuFilters();

        $this->registerFileViewFinder();

        $this->registerResourceMapper();

        Blade::extend(function($view, $compiler){
            $pattern = $compiler->createMatcher('toJsTree');
            return preg_replace($pattern, '$1<?php $f = new \BeeTree\Support\HtmlPrinter(); echo $f->toJsTree$2 ?>', $view);
        });

        Blade::extend(function($view, $compiler){
            $pattern = $compiler->createMatcher('guessTrans');
            return preg_replace($pattern, '$1<?php echo \Cmsable\Lang\OptionalTranslator::guess$2 ?>', $view);
        });

    }

    protected function registerSiteTreeModel(){

        $pageClass = $this->app['config']->get('cmsable.page_model');

        AttributeDispatcher::extend('bitmask', function($bitKey, $bitName){
            return new BitMaskAttribute($bitKey, $bitName);
        });


        $pageClass = $pageClass ?: 'Cmsable\Model\Page';

        $this->app->bind('Cmsable\Model\SiteTreeModelInterface', function($app) use ($pageClass){

            return $app->make('Cmsable\Model\AdjacencyListSiteTreeModel',[$pageClass]);

        });

    }

    protected function registerTreeModelManager(){

        $interface = 'Cmsable\Model\TreeModelManagerInterface';

        $this->app->singleton($interface, function($app){

            return $app->make('Cmsable\Model\TreeModelManager');

        });

        $this->app->afterResolving($interface, function($manager, $app) use ($interface){

            if ($app->resolved($interface)) {
                return;
            }

            if (!$this->hasArrayAdminModel()) {
                return;
            }

            $adminScope = $this->getAdminArrayScope();

            $adminModel = $app->make(
                'Cmsable\Model\ArraySiteTreeModel',[
                    'Cmsable\Model\GenericPage',
                    $adminScope->getModelRootId()
            ]);
            $adminModel->setEventBus(new IlluminateBus($app['events']));

            $adminModel->setPathPrefix($adminScope->getPathPrefix());

            $adminModel->provideArray(function($model){
                return include $this->getAdminTreeModelFile();
            });

            OptionalTranslator::provideTranslator(function() use ($app) {
                return $app['translator'];
            });

            $manager->set($adminScope, $adminModel);

        });

    }

    protected function registerTreeScopeRepository(){

        $interface = 'Cmsable\Routing\TreeScope\RepositoryInterface';

        $this->app->singleton($interface, function($app){

            return $app->make('Cmsable\Routing\TreeScope\RootNodeRepository');

        });

        $this->app->afterResolving($interface, function($repo, $app) use ($interface){

            if ($app->resolved($interface)) {
                return;
            }

            if (!$this->hasArrayAdminModel()) {
                return;
            }

            $repo->addManualScope($this->getAdminArrayScope());

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

        // The controller dispatcher has to be instantiated to listen to
        // router.matched

        $dispatcher = new ControllerDispatcher($this->app['router'], $this->app);

        $this->app->instance('illuminate.route.dispatcher', $dispatcher);

        // Caution: If the priority of this listener is lower than
        // PageTypeRouter, the controllerDispatcher wont geht useful
        // information if no page was found
        $this->app['events']->listen('router.matched', function($route, $request) use ($dispatcher) {
            $dispatcher->configure($route, $request);
        }, 10);

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

        PageType::setViewBootstrap(function(){
            $repo = $this->app->make('Cmsable\PageType\RepositoryInterface');
            $this->app['events']->fire('cmsable.pagetype-views-requested', [$repo]);
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

        $this->app->alias('cmsable.pageTypes','Cmsable\PageType\CurrentPageTypeProviderInterface');

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

       $this->app->instance('cmsable.cms', $cmsApp);
       $this->app->instance('Cmsable\Cms\Application', $cmsApp);

       if(!$this->app->runningInConsole()) {
           $this->app['Illuminate\Contracts\Http\Kernel']->prependMiddleWare(
                'Cmsable\Http\CmsRequestInjector'
           );
       }

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

        // Setup inverse pagetype routing
        $this->app['events']->listen(
            'router.matched',
            'Cmsable\Routing\PageTypeRouter@setPageType',
            20
        );

    }

    protected function registerUrlGenerator()
    {

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
            $urlGenerator->setPageTypes($app['Cmsable\PageType\RepositoryInterface']);
            $urlGenerator->setRouter($app['router']);

            return $urlGenerator;
        });

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

            $class = 'Cmsable\Cms\Action\Registry';

            return $app->make($class,[
                new NamedGroupCreator,
                new ClassResourceTypeIdentifier
            ]);

        });

    }

    protected function registerUserProvider(){

        $this->app->singleton('Cmsable\Auth\CurrentUserProviderInterface', function($app){

            $providerClass = $app['config']->get('cmsable.user_provider');

            return $app->make($providerClass);

        });

    }

    protected function createMenuFilters(){

        $this->app->singleton('Cmsable\Html\MenuFilterRegistry', function($app){
            return new MenuFilterRegistry;
        });

        $this->app->afterResolving('Cmsable\Html\MenuFilterRegistry', function($registry){

            $registry->filter('default',function($page){
                return (bool)$page->show_in_menu;
            });

            $registry->filter('aside_menu',function($page){
                return (bool)$page->show_in_aside_menu;
            });

            $registry->filter('*',function($page){
                  return (bool)$page->exists;
            });


            // Check if permit is installed, if it is add its access checker
            if (!$this->app->bound('Permit\Access\CheckerInterface')) {
                return;
            }

            $provider = $this->app['Cmsable\Auth\CurrentUserProviderInterface'];
            $checker = $this->app['Permit\Access\CheckerInterface'];

            $registry->filter('*', function($page) use ($provider, $checker){
                if ($page->view_permission == 'page.public-view') {
                    return true;
                }
                return $checker->hasAccess($provider->current(), $page->view_permission);
            });

            if (!$visibilityFlags = $this->app['config']['cmsable']['visibility-flags']) {
                return;
            }

            if (!in_array('show_when_authorized', $visibilityFlags)) {
                return;
            }

            $registry->filter('*', function($page) use ($provider, $checker){
                if (!$page->show_when_authorized && !$provider->current()->isGuest()) {
                    return false;
                }
                return true;
            });

        });

    }

    protected function registerRedirectController(){

        $routePrefix = $this->app['config']->get('cmsable.redirect-controller.routename-prefix');

        $this->app['router']->get(
            "$routePrefix",
            ['as'=>"$routePrefix",'uses'=>'Cmsable\Controller\RedirectorController@index']
        );

    }

    public function boot(){

        $this->injectControllerDispatcher();

        $this->registerPackageLang();

        $this->registerSiteTreeControllerRoute();

        $this->registerRedirectController();

        $this->registerPageQueryFactory();

        $app = $this->app;

        $this->app->validator->resolver(function($translator, $data, $rules, $messages) use ($app){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->setRouter($app['router']);
            return $validator;
        });

        $this->registerBreadcrumbs();
        $this->registerMenu();

        $this->setAdminAuthSettings();

        $this->app['cmsable.cms']->whenScope('*', function ($scope, $route, $request, $page)
        {
            return;
            if ($page && ! $this->app['auth']->allowed($page->view_permission)) {

                $intendedUrl = $this->app['url']->full();
                if (!str_contains($intendedUrl, '_debugbar')) {
                    $this->app['session']->set('url.intended', $intendedUrl);
                }

                return $this->app['redirect']->to('/session/create')->withErrors([
                    $this->app['translator']->get('user.noaccess')
                ]);
            }
        });

        $this->registerTextParser();

        $this->registerMailer();

        $this->registerTranslator();

        $this->registerPageTypeNamer();

    }

    protected function registerPageQueryFactory()
    {
        $class = 'Cmsable\Model\PageQueryFactory';
        $this->app->alias('cmsable.pagequery-factory', $class);

        $this->app->singleton('cmsable.pagequery-factory', function($app) use ($class){
            return new $class(
                $app['Cmsable\Model\SiteTreeModelInterface']->makeNode(),
                $app['Cmsable\PageType\ConfigRepositoryInterface'],
                $app['Cmsable\Html\MenuFilterRegistry']
            );
        });
    }

    protected function setAdminAuthSettings()
    {
        $this->app['cmsable.cms']->whenScope('admin', function (){
            $this->app['auth']->forceActual(true);
        });
    }

    protected function registerBreadcrumbNodeCreator()
    {

        $interface = 'Cmsable\Html\Breadcrumbs\NodeCreatorInterface';

        $this->app->singleton($interface, function($app){
            return $app['Cmsable\Model\SiteTreeModelInterface'];
        });
    }

    protected function registerBreadcrumbStore()
    {

        $interface = 'Cmsable\Html\Breadcrumbs\StoreInterface';
        $this->app->singleton($interface, function($app){
            return $app->make('Cmsable\Html\Breadcrumbs\SessionStore');
        });

    }

    protected function registerCrumbsCreator()
    {
        $interface = 'Cmsable\Html\Breadcrumbs\CrumbsCreatorInterface';
        $this->app->singleton($interface, function($app){
            return $app->make('Cmsable\Html\Breadcrumbs\SiteTreeCrumbsCreator');
        });
    }

    protected function registerBreadcrumbs(){

        $this->registerBreadcrumbNodeCreator();

        $this->registerBreadcrumbStore();

        $this->registerCrumbsCreator();

        $serviceProvider = $this;

        $this->app->alias('cmsable.breadcrumbs','Cmsable\Html\Breadcrumbs\Factory');

        $this->app->singleton('cmsable.breadcrumbs', function($app) use ($serviceProvider){

//             $factory = $app->make('Cmsable\Html\Breadcrumbs\Factory');

            $factory = new BreadcrumbFactory(
                $app->make('Cmsable\Html\Breadcrumbs\CrumbsCreatorInterface'),
                $app->make('router'),
                $app->make('Cmsable\Html\Breadcrumbs\StoreInterface')
            );

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
            "$routePrefix/move/{sitetree}",
            ['as'=>"$routePrefix.move",'uses'=>'Cmsable\Controller\SiteTree\SiteTreeController@move']
        );

        $this->app['router']->get(
            "$routePrefix/js-config",
            ['as'=>"$routePrefix.jsconfig",'uses'=>'Cmsable\Controller\SiteTree\SiteTreeController@getJsConfig']
        );

        $this->app['router']->resource("$routePrefix",'Cmsable\Controller\SiteTree\SiteTreeController');

        $this->registerPluginDispatcher();

    }

    protected function registerPluginDispatcher(){

        $this->app->afterResolving('Cmsable\Controller\SiteTree\SiteTreeController', function($controller, $app){

            $dispatcher = new Dispatcher($this->app['Cmsable\PageType\RepositoryInterface'],
                                         $this->app['events'],
                                         $this->app,
                                         $app['formobject.factory']);
        });


    }

    protected function registerFileViewFinder(){

        $oldFinder = $this->app['view.finder'];

        $this->app->bindShared('view.finder', function($app) use ($oldFinder)
        {
            return FallbackFileViewFinder::fromOther($oldFinder);

        });

    }

    protected function registerResourceMapper()
    {

        $this->app->alias('cmsable.resource-mapper', 'Cmsable\Resource\Contracts\Mapper');

        $this->app->singleton('cmsable.resource-mapper', function($app){
            return $app->make('Cmsable\Resource\Mapper');
        });

        $this->registerResourceMapperHook();

        $this->registerResourceDistributor();
        $this->registerRequestDecoratorHook();
        $this->registerModelFinder();
        $this->registerResourceFormHook();
        $this->registerInputCaster();
    }

    protected function registerResourceDetector()
    {
        $this->app->alias('cmsable.resource-detector', 'Cmsable\Resource\Contracts\Detector');

        $this->app->singleton('cmsable.resource-detector', function($app){
            return $app->make('Cmsable\Resource\PathDetector');
        });
    }

    protected function registerResourceDistributor()
    {

        $this->app->alias('cmsable.resource-distributor', 'Cmsable\Resource\Distributor');

        $this->app->singleton('cmsable.resource-distributor', function($app){
            return new Distributor($app->make('cmsable.resourcemapper'));
        });
    }

    protected function registerResourceMapperHook()
    {
        $this->app->resolving(function(ReceivesResourceMapper $mapperUser, $app){
            $mapperUser->setResourceMapper($app->make('cmsable.resourcemapper'));
        });
    }

    protected function registerRequestDecoratorHook()
    {
        $this->app->resolving(function(DecoratesRequest $decorator, $app){
            $decorator->decorate($app['request']);
        });
    }

    protected function registerModelFinder()
    {
        $this->app->bind('Cmsable\Resource\Contracts\ModelFinder', function($app){
            return $app->make('Cmsable\Resource\EloquentModelFinder');
        });
    }

    protected function registerResourceFormHook()
    {
        $this->app->resolving(function(ResourceForm $form, $app){
            $app->make('Cmsable\Resource\Distributor')->forwardResourceForm($form);
        });
    }

    protected function registerInputCaster()
    {

        $this->app->bind('XType\Casting\Contracts\InputCaster', function($app){
            return $app->make('FormObject\Support\Laravel\Http\InputCaster');
        });
    }

    protected function registerTextParser()
    {

        $this->app->bind('Cmsable\View\TextParserInterface', function($app){
            $queue = $app->make('Cmsable\View\TextParserQueue');
            $queue->setEventBus(new IlluminateBus($app['events']));
            return $queue;
        });

        $this->registerAssignedDataParser();

    }

    protected function registerAssignedDataParser()
    {
        $this->app['events']->listen('cmsable.text-parser-load', function($queue){
            $queue->add($this->app->make('Cmsable\View\AssignedDataParser'));
        });
    }

    protected function registerMailer()
    {
        $this->app->bind('Cmsable\Mail\MailerInterface', function($app){
            return $app->make('Cmsable\Mail\Mailer');
        });
    }

    protected function registerTranslator()
    {
        $this->app->singleton('Cmsable\Translation\TranslatorInterface', function($app){
            return $app->make('Cmsable\Translation\Translator');
        });
    }

    protected function registerPageTypeNamer()
    {
        $this->app['events']->listen(
            'cmsable.pagetype-views-requested',
            'Cmsable\PageType\TranslationNamer@setNames'
        );
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

    protected function hasArrayAdminModel()
    {
        return (bool)$this->getAdminTreeModelFile();
    }

    protected function getAdminArrayScope()
    {
        if ($this->adminArrayScope) {
            return $this->adminArrayScope;
        }

        $this->adminArrayScope = new TreeScope();
        $this->adminArrayScope->setModelRootId(0);
        $this->adminArrayScope->setName('admin');
        $this->adminArrayScope->setPathPrefix('admin');

        return $this->adminArrayScope;
    }

    protected function getAdminTreeModelFile()
    {

        if ($this->adminTreeModelFile !== null) {
            return $this->adminTreeModelFile;
        }

        $treeConfig = $this->app['config']['cmsable.admintree'];

        if ($treeConfig == 'db') {
            $this->adminTreeModelFile = '';
            return $this->adminTreeModelFile;
        }

        if ($treeConfig == 'default') {
            $this->adminTreeModelFile = $this->getCmsPackagePath().'/resources/sitetrees/admintree.php';
            return $this->adminTreeModelFile;
        }

        $this->adminTreeModelFile = $treeConfig;

        return $this->adminTreeModelFile;

    }

    protected function getAdminViewPath()
    {
        if ($this->adminViewPath) {
            return $this->adminViewPath;
        }

        $this->adminViewPath = $this->getCmsPackagePath().'/resources/views/admin';

        return $this->adminViewPath;

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
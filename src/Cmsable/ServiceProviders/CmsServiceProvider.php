<?php namespace Cmsable\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Cmsable\Model\AdjacencyListSiteTreeModel;
use Cmsable\Cms\ControllerDescriptorLoaderManual;
use Cmsable\Html\Menu;
use Cmsable\Html\MenuFilter;
use Cmsable\Html\SiteTreeUrlGenerator;
use Cmsable\Routing\SiteTreeRouter;
use Cmsable\Validators\CmsValidator;
use ConfigurableClass\LaravelConfigModel;
use DB;

use FormObject\Registry;


class CmsServiceProvider extends ServiceProvider{

    public function register(){

        $this->app->singleton('adminSiteTree', function(){
            return new AdjacencyListSiteTreeModel('\Cmsable\Model\Page',1);
        });

        $this->app->singleton('pageTypes', function(){
            return new ControllerDescriptorLoaderManual($this->app['events']);
        });

        $this->app->singleton('adminMenu', function(){
            return new Menu($this->app->make('adminSiteTree'),
                            new MenuFilter());
        });

        $this->app->singleton('ConfigurableClass\ConfigModelInterface', function(){
            $model = $this->app->make('ConfigurableClass\LaravelConfigModel');
            $model->setTableName('controller_config');
            return $model;
        });

        $this->app->singleton('siteTree', function(){
            return new AdjacencyListSiteTreeModel('\Cmsable\Model\Page',2);
        });

        $this->app->singleton('menu', function(){
            return new Menu($this->app->make('siteTree'),
                            new MenuFilter());
        });

        $this->app['router'] = $this->app->share(function($app)
        {
            $router = new SiteTreeRouter($app['events'], $app);

            // If the current application environment is "testing", we will disable the
            // routing filters, since they can be tested independently of the routes
            // and just get in the way of our typical controller testing concerns.
            if ($app['env'] == 'testing')
            {
                $router->disableFilters();
            }

            return $router;
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

    public function boot(){
        $this->app->validator->resolver(function($translator, $data, $rules, $messages){
            $validator = new CmsValidator($translator, $data, $rules, $messages);
            $validator->addSiteTreeLoader($this->app['siteTree']);
            $validator->addSiteTreeLoader($this->app['adminSiteTree']);
            $validator->setRouter($this->app['router']);
            return $validator;
        });
    }
}
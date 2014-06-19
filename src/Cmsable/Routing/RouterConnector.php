<?php namespace Cmsable\Routing;

use Illuminate\Routing\Router;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Cms\ControllerDescriptorLoaderInterface;
use Input;
use Route;

class RouterConnector{

    protected $cmsRoutes = array();

    protected $_currentPage = NULL;

    protected $_isSiteTreeRoute = NULL;

    protected $router;

    protected $descriptorLoader;

    public function __construct(ControllerDescriptorLoaderInterface $loader){
        $this->descriptorLoader = $loader;
    }

    public function addCmsRoute($uri, SiteTreeModelInterface $siteTreeModel, $name=NULL){

        $siteTreeModel->setPathPrefix($uri);

        $verbs = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');
        $route = new SiteTreeRoute($verbs, $uri, function(){});
        $route->setTreeLoader($siteTreeModel);
        $route->setDescriptorLoader($this->descriptorLoader);

        if(!$name){
            $name = $uri;
        }

        $this->cmsRoutes[$name] = $route;
    }

    public function controllerDescriptors(){
        return $this->descriptorLoader;
    }

    public function pageTypes(){
        return $this->controllerDescriptors();
    }

    public function getTreeModel($routeName){
        return $this->cmsRoutes[$routeName]->treeLoader();
    }

    public function getCmsRoutes(){
        return array_values($this->cmsRoutes);
    }

    public function register(Router $router){

        $cms = $this;
        $this->router = $router;

        $router->before(function($request) use($router, $cms){
            foreach($cms->getCmsRoutes() as $route){
                $router->getRoutes()->add($route);
            }
        });
    }

    public function findRouteForSiteTreeObject($page){
        foreach($this->cmsRoutes as $name=>$route){
            if($route->treeLoader()->pageById($page->id)){
                return $route;
            }
        }
    }

    public function findBestMatchingCmsRoute($path){

        $route = $this->router->getCurrentRoute();
        if($route instanceof SiteTreeRoute){
            return $route;
        }

        $foundRoute = NULL;
        $mostMatchingChars = 0;

        foreach($this->cmsRoutes as $name=>$route){

            $routeUri = $route->uri();
            $routeUriLength = mb_strlen($routeUri);

            if(mb_strpos($path, $routeUri) !== FALSE){
                if($routeUriLength > $mostMatchingChars){
                    $foundRoute = $route;
                    $mostMatchingChars = $routeUriLength;
                }
            }
        }

        if(!$foundRoute){
            $foundRoute = $this->cmsRoutes['default'];
        }
        return $foundRoute;
    }

    public function inSiteTree(){
        if($this->_isSiteTreeRoute === NULL){
            if($currentRoute = $this->router->getCurrentRoute()){
                $this->_isSiteTreeRoute = ($currentRoute instanceof SiteTreeRoute);
            }
        }
        return $this->_isSiteTreeRoute;
    }

    public function currentPage(){
        if($this->_currentPage === NULL){
            if($route = $this->findBestMatchingCmsRoute(Input::path())){
                $this->_currentPage = $route->currentPage();
                if(!$this->_currentPage){
                    $this->_currentPage = $route->fallbackPage();
                }
            }
        }
        return $this->_currentPage;
    }

}
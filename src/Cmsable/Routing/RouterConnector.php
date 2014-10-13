<?php namespace Cmsable\Routing;

use Illuminate\Routing\Router;
use Cmsable\Auth\CurrentUserProviderInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Routing\Routable\CreatorInterface;
use Cmsable\Cms\PageTypeRepositoryInterface;
use Input;
use Route;

class RouterConnector implements RouteInspectorInterface{

    protected $cmsRoutes = array();

    protected $matchedNode = NULL;

    protected $matchedRoutable = NULL;

    protected $_isSiteTreeRoute = NULL;

    protected $router;

    protected $pageTypes;

    protected $userProvider;

    public function __construct(PageTypeRepositoryInterface $pageTypes, CurrentUserProviderInterface $provider){
        $this->pageTypes = $pageTypes;
        $this->userProvider = $provider;
    }

    public function currentUser(){
        return $this->userProvider->current();
    }

    public function getUserProvider(){
        return $this->userProvider;
    }

    public function addCmsRoute(RouteInspectorInterface $route, $name){

        $this->cmsRoutes[$name] = $route;
    }

    public function pageTypes(){
        return $this->pageTypes;
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

    public function getMatchedNode(){
        if($this->matchedNode === NULL){
            if($route = $this->findBestMatchingCmsRoute(Input::path())){
                $this->matchedNode = $route->getMatchedNode();
                if(!$this->matchedNode){
                    $this->matchedNode = $route->getFallbackPage();
                }
            }
        }
        return $this->matchedNode;
    }

    public function getMatchedRoutable(){
        if($this->matchedRoutable === NULL){
            if($route = $this->findBestMatchingCmsRoute(Input::path())){
                $this->matchedRoutable = $route->getMatchedRoutable();
            }
        }
        return $this->matchedRoutable;
    }

    public function getFallbackPage(){
        if($route = $this->findBestMatchingCmsRoute(Input::path())){
            return $route->getFallbackPage();
        }
    }

}
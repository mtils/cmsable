<?php namespace Cmsable\Routing;

use Illuminate\Routing\Router;
use Input;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SiteTreeRouter extends Router{

    protected $cmsRoutes = array();

    protected $_currentPage = NULL;

    protected $_isSiteTreeRoute = NULL;

    public function cms($uri='/', $siteTreeClass='SiteTree'){

        $verbs = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');
        $route = new SiteTreeRoute($verbs, $uri, function(){
            return NULL; // Wird wieder ueberschrieben
        });
        $loader = \App::make(lcfirst($siteTreeClass));
        $loader->setPathPrefix($uri);
        $route->setTreeLoader($loader);
        $this->cmsRoutes[] = $route;
        $this->routes->add($route);
    }

    public function findRouteForSiteTreeObject($page){
        foreach($this->cmsRoutes as $route){
            if($route->treeLoader()->pageById($page->id)){
                return $route;
            }
        }
    }

    public function findBestMatchingCmsRoute($path){

        $route = $this->getCurrentRoute();
        if($route instanceof SiteTreeRoute){
            return $route;
        }

        $foundRoute = NULL;
        $mostMatchingChars = 0;

        foreach($this->cmsRoutes as $route){

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
            $foundRoute = $this->cmsRoutes[0];
        }
        return $foundRoute;
    }

    public function inSiteTree(){
        if($this->_isSiteTreeRoute === NULL){
            if($currentRoute = $this->getCurrentRoute()){
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
//             else{
//                 $this->_currentPage = $this->
//             }
        }
        return $this->_currentPage;
    }

}
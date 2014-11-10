<?php namespace Cmsable\Html;

use Illuminate\Routing\UrlGenerator;
use Route;
use Cmsable\Model\SiteTreeNodeInterface;
use CMS;
use Cmsable\Routing\SiteTreePathFinderInterface;

class SiteTreeUrlGenerator extends UrlGenerator{

    protected $siteTreeLoader;

    protected $pathFinder;

    /**
     * Generate a absolute URL to the given path.
     *
     * @param  mixed  $path or SiteTreeNodeInterface Instance
     * @param  mixed  $extra
     * @param  bool  $secure
     * @return string
     */
    public function to($path, $extra = array(), $secure = null)
    {

        // Page object passed
        if(is_object($path) && $path instanceof SiteTreeNodeInterface){
            if(starts_with($path->getPath(),'http:')){
                $path = $path->getPath();
            }
            else{
                $path = $this->pathFinder->toPage($path);
            }
        }

        // PageTypeId passed
        elseif(is_string($path) && CMS::pageTypes()->has($path)){
            $path = $this->pathFinder->toPageType($path);
        }

        // Path passed inside CMS-SiteTree
        elseif(is_string($path) && CMS::inSiteTree()){
            if($extra && !isset($extra[0])){
                $extra = array_values($extra);
                $extraPath = implode('/',$extra);
                $path = trim($path,'/') . '/' . trim($extraPath,'/');
                $extra = array();
            }
        }
        return parent::to($path, $extra, $secure);
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = array(), $absolute = true, $route = null)
    {
        if($path = $this->pathFinder->toRouteName($name, $parameters)){
            if($absolute){
                return $this->to($path);
            }
            return $path;
        }

        return parent::route($name, $parameters, $absolute, $route);
    }

    /**
    * Get the URL to a controller action.
    *
    * @param  string  $action
    * @param  mixed   $parameters
    * @param  bool    $absolute
    * @return string
    */
    public function action($action, $parameters = array(), $absolute = true)
    {

        if($path = $this->pathFinder->toControllerAction($action, $parameters)){

            if(!$absolute){
                return $path;
            }

            return $this->to($path);
        }

        return parent::action($action, $parameters, $absolute);

    }

    public function page($page=NULL, $extra = array(), $secure = null){

        if($page === NULL){
            $page = CMS::getMatchedNode();
        }

        if($page instanceof SiteTreeNodeInterface){
            return $this->to($page, $extra, $secure);
        }

    }

    public function currentPage($extra=[], $secure = null){

        return $this->to(CMS::getMatchedNode(), $extra, $secure);

    }

    public function getPathFinder(){
        return $this->pathFinder;
    }

    public function setPathFinder(SiteTreePathFinderInterface $pathFinder){
        $this->pathFinder = $pathFinder;
        return $this;
    }
}

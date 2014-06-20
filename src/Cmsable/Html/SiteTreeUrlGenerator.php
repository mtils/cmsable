<?php namespace Cmsable\Html;

use Illuminate\Routing\UrlGenerator;
use Route;
use Cmsable\Model\SiteTreeNodeInterface;
use CMS;

class SiteTreeUrlGenerator extends UrlGenerator{

    protected $siteTreeLoader;
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
        if(is_object($path)){
            if($path instanceof SiteTreeNodeInterface){
                $page = $path;
                if(!$path = $page->getPath()){
                    if($route = CMS::findRouteForSiteTreeObject($page)){
                        $path = ltrim($route->treeLoader()->pathById($page->id),'/');
                    }
                }
            }
        }
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
    * Get the URL to a controller action.
    *
    * @param  string  $action
    * @param  mixed   $parameters
    * @param  bool    $absolute
    * @return string
    */
    public function action($action, $parameters = array(), $absolute = true)
    {
        if(!mb_strpos($action,'@')){
            if($page = CMS::currentPage()){
                if(!$parameters){
                    return $this->to($page) . '/' . ltrim($action,'/');
                }
                if(isset($parameters[0])){
                    array_unshift($parameters, $action);
                    return $this->to($page, $parameters);
                }
                else{
                    $actionParam = array('action'=>$action);
                    return $this->to($page, array_merge($actionParam, $parameters));
                }
            }
        }
        return parent::action($action, $parameters, $absolute);
    }
}
<?php namespace Cmsable\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;

use Cmsable\Html\SiteTreeUrlGenerator;

class SiteTreeUrlDispatcher extends UrlGenerator{

    use ScopeDispatcherTrait;

    /**
     * Generate a absolute URL to the given path.
     *
     * @param  mixed  $path or SiteTreeNodeInterface Instance
     * @param  mixed  $extra
     * @param  bool  $secure
     * @return string
     */
    public function to($path, $extra = array(), $secure = null){
        return $this->forwarder()->to($path, $extra, $secure);
    }

    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @param  Route  $route
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = array(), $absolute = true, $route = null)
    {
        return $this->forwarder()->route($name, $parameters, $absolute, $route);
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
        return $this->forwarder()->action($action, $parameters, $absolute);
    }

    public function page($page=NULL, $extra = array(), $secure = null){

        return $this->forwarder()->page($page, $extra, $secure);

    }

    public function currentPage($extra=[], $secure = null){

        return $this->forwarder()->currentPage($extra, $secure);

    }

}
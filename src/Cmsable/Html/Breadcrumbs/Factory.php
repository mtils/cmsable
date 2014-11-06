<?php namespace Cmsable\Html\Breadcrumbs;

use Closure;
use UnderflowException;
use Illuminate\Routing\Router;

use Cmsable\Support\EventSenderTrait;


class Factory{

    use EventSenderTrait;

    public static $loadCallbacksEvent = 'cmsable::breadcrumbs-load';

    protected $crumbsCreator;

    protected $router;

    protected $currentRoute;

    protected $routeCallbacks = [];

    protected $compiledCrumbs = [];

    public function __construct(CrumbsCreatorInterface $creator,
                                Router $router){

        $this->crumbsCreator = $creator;
        $this->router = $router;

    }

    public function register($name, Closure $closure){
        $this->routeCallbacks[$name] = $closure->bindTo($this);
        return $this;
    }

    public function exists($name){
        $routeCallbacks = $this->getRouteCallbacks();
        return isset($routeCallbacks[$name]);
    }

    public function get($name=NULL){

        $params = [];

        if($name === NULL){
            list($name, $params) = $this->currentRoute();
        }

        $indexName = ($name == NULL) ? '#default#' : $name;

        if(isset($this->compiledCrumbs[$indexName])){
            return $this->compiledCrumbs[$indexName];
        }

        $crumbs = $this->createCrumbs();

        if($name){
            $factoryParams = array_merge([$name, $crumbs], $params);
            call_user_func_array([$this,'fill'],$factoryParams);
        }

        $this->compiledCrumbs[$indexName] = $crumbs;

        return $crumbs;

    }

    public function fill($name, $breadcrumbs){

        $routeCallbacks = $this->getRouteCallbacks();

        if(!isset($routeCallbacks[$name])){
            return $breadcrumbs;
        }

        $args = func_get_args();

        array_shift($args);

        return call_user_func_array($routeCallbacks[$name], $args);

    }

    public function getCrumbsCreator(){
        return $this->crumbsCreator;
    }

    public function setCrumbsCreator(CrumbsCreatorInterface $creator){
        $this->crumbsCreator = $creator;
        return $this;
    }

    public function createCrumbs(){
        return $this->crumbsCreator->createCrumbs();
    }

    public function getRouteCallbacks(){
        $this->fireEvent(static::$loadCallbacksEvent, [$this], $once=true);
        return $this->routeCallbacks;
    }

    protected function currentRoute()
    {
        if ($this->currentRoute)
            return $this->currentRoute;

        $route = $this->router->current();

        if (is_null($route))
            return $this->currentRoute = array('', array());

        $name = $route->getName();

//         if (is_null($name)) {
//             $uri = head($route->methods()) . ' ' . $route->uri();
//             throw new UnderflowException("The current route ($uri) is not named - please check routes.php for an \"as\" parameter");
//         }

        $params = $route->parameters();

        return $this->currentRoute = array($name, $params);

    }

    public function setCurrentRoute($name){
        $params = array_slice(func_get_args(), 1);
        $this->setCurrentRouteArray($name, $params);
    }

    public function setCurrentRouteArray($name, $params = array()){
        $this->currentRoute = array($name, $params);
    }

    public function clearCurrentRoute(){
        $this->currentRoute = null;
    }

}
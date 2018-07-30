<?php namespace Cmsable\Routing;

use Illuminate\Routing\ControllerDispatcher as IlluminateDispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;

use Cmsable\Http\CmsRequestInterface;
use Cmsable\Model\SiteTreeNodeInterface;


class ControllerDispatcher extends IlluminateDispatcher
{

    protected $creator;

    protected $page;

    /**
     * Dispatch a request to a given controller and method.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {

        // This is not very nice but with the current structure we have to throw
        // the previously created controller away.
        // For further implementations more cleanup has to be done
        $instance = $this->makeController(is_object($controller) ? get_class($controller) : $controller);

        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $instance, $method
        );

        if ($this->creator && method_exists($this->creator, 'modifyMethodParameters')) {
            $this->creator->modifyMethodParameters($instance, $method, $parameters);
        }

        if (method_exists($instance, 'callAction')) {
            return $instance->callAction($method, $parameters);
        }

        return $instance->{$method}(...array_values($parameters));

    }

    public function configure(Route $route, CmsRequestInterface $request)
    {

        $cmsPath = $request->getCmsPath();

        if (!$pageType = $cmsPath->getPageType()) {
            $this->resetCreator();
            $this->resetPage();
            return;
        }

        if ($node = $cmsPath->getMatchedNode()) {
            $this->setPage($node);
        } else {
            $this->resetPage();
        }

        if(!$creatorClass = $pageType->getControllerCreatorClass()){
            $this->resetCreator();
            return;
        }

        $this->setCreator($this->container->make($creatorClass));

    }

    /**
     * Make a controller instance via the IoC container.
     *
     * @param  string  $controller
     * @return mixed
     */
     protected function makeController($controller)
    {

        if (!$this->creator) {
            return $this->container->make($controller);
        }

        return $this->creator->createController($controller, $this->getPage());

    }

    /**
     * Call the given controller instance method.
     *
     * @param  \Illuminate\Routing\Controller  $instance
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $method
     * @return mixed
     */
    protected function call($instance, $route, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $instance, $method
        );

        if ($this->creator && method_exists($this->creator, 'modifyMethodParameters')) {
            $this->creator->modifyMethodParameters($instance, $method, $parameters);
        }

        return $instance->callAction($method, $parameters);
    }

    public function getCreator(){
        return $this->creator;
    }

    public function setCreator(ControllerCreatorInterface $creator){
        $this->creator = $creator;
        return $this;
    }

    public function resetCreator(){
        $this->creator = NULL;
        return $this;
    }

    public function getPage(){
        return $this->page;
    }

    public function setPage(SiteTreeNodeInterface $page){
        $this->page = $page;
        return $this;
    }

    public function resetPage(){
        $this->page = NULL;
        return $this;
    }

}

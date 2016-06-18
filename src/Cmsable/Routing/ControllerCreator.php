<?php namespace Cmsable\Routing;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Support\HoldsContainer;
use Cmsable\Support\ReceivesContainerWhenResolved;
use Cmsable\PageType\ConfigRepositoryInterface;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\Container\Container;



class ControllerCreator implements ControllerCreatorInterface, ReceivesContainerWhenResolved
{

    use HoldsContainer;

    /**
     * @var Cmsable\Model\SiteTreeNodeInterface
     **/
    protected $page;

    /**
     * @var \Cmsable\PageType\ConfigRepositoryInterface
     **/
    protected $configRepository;

    /**
     * @var array
     **/
    protected $configByPageId = [];

    /**
     * Creates a controller while routing to a page. This method is used
     * to configure dependencies of you controller according to a page.
     * You can also configure your controller directly like setting a
     * a layout
     *
     * @param string $controllerName The classname of the routed controller
     * @param \Cmsable\Model\SiteTreeNodeInterface $page (optional) Null if no match
     * @return \Illuminate\Routing\Controller
     **/
    public function createController($name, SiteTreeNodeInterface $page=null)
    {

        $this->page = $page;

        $controller = $this->makeController($name);

        $this->modifyController($controller, $page);

        return $controller;

    }

    /**
     * This is a stub method. If you want to just modify the controller and not make it, overwrite
     * this method
     *
     * @param string $controllerName The classname of the routed controller
     * @param \Cmsable\Model\SiteTreeNodeInterface $page (optional) Null if no match
     * @return null
     **/
    public function modifyController(Controller $controller, SiteTreeNodeInterface $page=null) {}

    /**
     * Modify any dependencies of a route call. This allows hooking into method
     * injection
     * This method checks for a method on$method of this class. So for example
     * if a method named onIndex exists, it will be called with $controller and $parameters
     *
     * @param \Illuminate\Routing\Controller $controller
     * @param string $method
     * @param array $parameters
     * @return null
     **/
    public function modifyMethodParameters(Controller $controller, $method, array $parameters)
    {
        $myMethod = 'on' .ucfirst($method);
        if (method_exists($this, $myMethod)) {
            call_user_func([$this, $myMethod], $controller, $parameters);
        }
    }

    /**
     * @param string $controllerName The classname of the routed controller
     * @return \Illuminate\Routing\Controller
     **/
    protected function makeController($name)
    {
        if (!$this->container) {
            return new $name;
        }
        return $this->container->make($name);
    }

    /**
     * @return \Cmsable\Model\SiteTreeNodeInterface
     **/
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param \Cmsable\Model\SiteTreeNodeInterface $page
     * @return self
     **/
    public function setPage(SiteTreeNodeInterface $page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param \Cmsable\Model\SiteTreeNodeInterface $page
     * @return \Cmsable\PageType\ConfigInterface
     **/
    public function config(SiteTreeNodeInterface $page=null)
    {

        $page = $page instanceof SiteTreeNodeInterface ? $page : $this->getPage();

        if (!$page) {
            return;
        }

        $id = $page->getIdentifier();

        if (isset($this->configByPageId[$id])) {
            return $this->configByPageId[$id];
        }

        $this->configByPageId[$id] = $this->getConfigRepository()->getConfig($page->getPageTypeId(), $id);

        return $this->configByPageId[$id];

    }

    /**
     * @return \Cmsable\PageType\ConfigRepositoryInterface
     **/
    public function getConfigRepository()
    {
        if ($this->configRepository) {
            return $this->configRepository;
        }
        if (!$this->container) {
            return $this->configRepository;
        }
        $this->configRepository = $this->container->make('Cmsable\PageType\ConfigRepositoryInterface');
        return $this->configRepository;
    }

    /**
     * @param Cmsable\PageType\ConfigRepositoryInterface $configRepo
     * @return self
     **/
    public function setConfigRepository(ConfigRepositoryInterface $configRepo)
    {
        $this->configRepository = $configRepo;
        return $this;
    }

}
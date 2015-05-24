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

        if($this->creator){

            Controller::setRouter($this->router);
            return $this->creator->createController($controller, $this->getPage());

        }

        return parent::makeController($controller);

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
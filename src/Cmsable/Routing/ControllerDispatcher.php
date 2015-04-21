<?php namespace Cmsable\Routing;

use Illuminate\Routing\ControllerDispatcher as IlluminateDispatcher;
use Illuminate\Routing\Controller;

use Cmsable\Model\SiteTreeNodeInterface;


class ControllerDispatcher extends IlluminateDispatcher{

    protected $creator;

    protected $page;

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
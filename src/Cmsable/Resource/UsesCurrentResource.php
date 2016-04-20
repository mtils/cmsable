<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\Distributor as DistributorContract;

trait UsesCurrentResource
{

    protected $distributor;

    protected $_resourceName = '';

    use ResourceBus;

    public function resourceName()
    {
        if ($this->_resourceName) {
            return $this->_resourceName;
        }

        return $this->distributor->getCurrentResource();
    }

    public function setResourceName($resourceName)
    {
        $this->_resourceName = $resourceName;
        return $this;
    }

    public function modelClass()
    {
        return $this->distributor->modelByResource($this->resourceName());
    }

    public function setResourceDistributor(DistributorContract $distributor)
    {
        $this->distributor = $distributor;
    }

    public function publish($action, $params)
    {
       $eventName = $this->eventName($this->resourceName().".$action");
       return $this->fire($eventName, $params);
    }

}

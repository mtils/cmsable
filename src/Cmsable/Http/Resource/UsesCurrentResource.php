<?php namespace Cmsable\Http\Resource;

use Illuminate\Http\Request;
use Cmsable\Resource\Contracts\ReceivesResourceMapper;
use Cmsable\Resource\UsesResourceMapper;
use Cmsable\Resource\ResourceBus;

trait UsesCurrentResource
{

    use UsesResourceMapper;
    use ResourceBus;

    public function resourceName()
    {
        return $this->resourceMapper->resourceByRequest($this);
    }

    public function modelClass()
    {
        return $this->resourceMapper->modelByResource($this->resourceName());
    }

    public function fireAction($action, $params)
    {
       $eventName = $this->eventName($this->resourceName().".$action");
       return $this->fire($eventName, $params);
    }

}
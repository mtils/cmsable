<?php namespace Cmsable\Resource;

use Signal\NamedEvent\BusHolderTrait;

trait ResourceBus
{

    use BusHolderTrait;

    protected $eventNamespace = 'resource::';

    /**
     * Return the single instance of resource bus
     *
     * @return \Signal\Contracts\NamedEvent\Bus
     **/
    public function getEventBus()
    {
        if (!$this->eventBus) {
            $this->eventBus = Bus::instance();
        }
        return $this->eventBus;
    }

    protected function eventName($name)
    {
        return $this->eventNamespace . $name;
    }

}
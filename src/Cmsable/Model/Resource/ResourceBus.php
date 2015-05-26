<?php namespace Cmsable\Model\Resource;

use Signal\NamedEvent\BusHolderTrait;

trait ResourceBus
{

    use BusHolderTrait;

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

}
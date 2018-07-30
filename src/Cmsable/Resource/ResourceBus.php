<?php namespace Cmsable\Resource;

use Ems\Contracts\Events\Bus as BusContract;

/**
 * Trait ResourceBus
 * @package Cmsable\Resource
 * @deprecated
 */
trait ResourceBus
{

    /**
     * @var Bus
     */
    protected $eventBus;

    /**
     * @var string
     */
    protected $eventNamespace = 'resource::';

    /**
     * Return the single instance of resource bus
     *
     * @return BusContract
     **/
    public function getEventBus()
    {
        if ($this->eventBus) {
            return $this->eventBus;
        }

        if (isset(static::$staticEventBus) && static::$staticEventBus) {
            return static::$staticEventBus;
        }

        if (!$this->eventBus) {
            $this->eventBus = Bus::instance();
        }
        return $this->eventBus;
    }

    /**
     * Fire an event with payload $payload. If $halt is set to true
     * stop propagation if some subscriber return not null
     *
     * @param string $event The event name
     * @param array $payload The event parameters
     * @param bool $halt Stop propagation on return values !== null
     * @return mixed
     **/
    protected function fire($event, $payload=[], $halt=false)
    {
        return $this->getEventBus()->fire($event, $payload, $halt);
    }

    /**
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @param  int  $priority
     * @return void
     */
    protected function listen($events, $listener, $priority = 0)
    {
        return $this->getEventBus()->listen($events, $listener, $priority);
    }

    protected function eventName($name)
    {
        return $this->eventNamespace . $name;
    }

}
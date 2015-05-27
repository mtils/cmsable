<?php namespace Cmsable\Resource;

use Signal\Support\Laravel\IlluminateBus;
use App;

class Bus extends IlluminateBus
{

    private static $singleInstance;

    protected $bootedResources = [];

    /**
     * Fire an event with payload $payload. If $halt is set to true
     * stop propagation if some subscriber return not null
     *
     * @param string $event The event name
     * @param array $payload The event parameters
     * @param bool $halt Stop propagation on return values !== null
     * @return mixed
     **/
    public function fire($event, $payload=[], $halt=false)
    {
        $resource = $this->getResource($event);

        if (!$this->wasBooted($resource)) {
            $this->bootResource($resource);
        }

        return parent::fire($event, $payload, $halt);
    }

    protected function getResource($event)
    {
        if (strpos($event, '.') === false) {
            return $event;
        }

        return explode('.', $event)[0];

    }

    protected function wasBooted($resource)
    {
        return isset($this->bootedResources[$resource]);
    }

    protected function bootResource($resource)
    {
        parent::fire("$resource.boot");
        $this->bootedResources[$resource] = true;
    }

    public static function instance()
    {
        if (!self::$singleInstance) {
            self::$singleInstance = new static(App::make('events'));
        }
        return self::$singleInstance;
    }

}
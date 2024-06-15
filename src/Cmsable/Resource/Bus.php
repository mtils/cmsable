<?php namespace Cmsable\Resource;

use App;
use Ems\Events\Bus as EmsBus;
use function func_get_args;


/**
 * Class Bus
 *
 * @package Cmsable\Resource
 * @deprecated
 */
class Bus extends EmsBus
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

    /**
     *
     * @param  string|array  $events
     * @param  mixed  $listener
     * @param  int  $priority
     * @return void
     */
    public function listen($events, $listener, $priority = 0)
    {
        if ($priority === 0) {
            $this->on($events, $listener);
            return;
        }

        if ($priority > 5) {
            $this->onBefore($events, $listener);
            return;
        }

        $this->onAfter($events, $listener);

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
        // Nowhere used
        parent::fire("$resource.boot");
        $this->bootedResources[$resource] = true;
    }

    public static function instance()
    {
        if (!self::$singleInstance) {

            self::$singleInstance = new static();

            $illuminateEvents = App::make('events');

            // Forward all events to the illuminate bus
            self::$singleInstance->on('*', function () use ($illuminateEvents) {
                $args = func_get_args();
                $event = array_shift($args);
                $illuminateEvents->dispatch($event, $args);
            });

            // Used in Ems\App\Providers\PackageServiceProvider
            self::$singleInstance->fire('resource::bus.started', [self::$singleInstance]);
        }
        return self::$singleInstance;
    }

}
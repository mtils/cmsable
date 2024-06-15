<?php namespace Cmsable\Support;

use InvalidArgumentException;
use OutOfBoundsException;

use function call_user_func;

trait EventSenderTrait{

    /**
     * @var ?callable
     */
    protected $eventDispatcher;

    protected $optionalFire = TRUE;

    protected $firedEvents = [];

    public function getEventDispatcher(){
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(callable $dispatcher){

        $this->eventDispatcher = $dispatcher;

    }

    protected function fireEvent($eventName, array $params=[], $fireOnce=FALSE){

        if($this->eventDispatcher){

            if($fireOnce && isset($this->firedEvents[$eventName])){
                return null;
            }

            $result = call_user_func($this->eventDispatcher, $eventName, $params);

            $this->firedEvents[$eventName] = TRUE;

            return $result;

        }
        elseif(!$this->optionalFire){
            throw new OutOfBoundsException('No Dispatcher setted');
        }

    }

}
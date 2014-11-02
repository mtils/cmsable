<?php namespace Cmsable\Support;

use InvalidArgumentException;
use OutOfBoundsException;

trait EventSenderTrait{

    protected $eventDispatcher;

    protected $optionalFire = TRUE;

    protected $firedEvents = [];

    public function getEventDispatcher(){
        return $this->eventDispatcher;
    }

    public function setEventDispatcher($dispatcher){

        if(!is_object($dispatcher) || !method_exists($dispatcher,'fire')){
            throw new InvalidArgumentException('$dispatcher has to have a fire method');
        }

        $this->eventDispatcher = $dispatcher;

    }

    protected function fireEvent($eventName, array $params=[], $fireOnce=FALSE){

        if($this->eventDispatcher){

            if($fireOnce && isset($this->firedEvents[$eventName])){
                return;
            }

            $result = $this->eventDispatcher->fire($eventName, $params);

            $this->firedEvents[$eventName] = TRUE;

            return $result;

        }
        elseif(!$this->optionalFire){
            throw new OutOfBoundsException('No Dispatcher setted');
        }

    }

}
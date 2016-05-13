<?php namespace Cmsable\Cms\Action;

use Cmsable\Auth\CurrentUserProviderInterface;
use UnexpectedValueException;

class Registry
{

    protected $creators = [];

    protected $itemCreators = [];

    protected $collectionCreators = [];

    protected $typeCreators = [];

    protected $identifier;

    protected $currentUserProvider;

    protected $tempGroup;

    protected $groupCreator;

    protected $currentActionNameProvider;

    public function __construct(GroupCreatorInterface $groupCreator,
                                ResourceTypeIdentifierInterface $identifier,
                                CurrentUserProviderInterface $currentUserProvider){

        $this->groupCreator = $groupCreator;
        $this->identifier = $identifier;
        $this->currentUserProvider = $currentUserProvider;

    }

    public function getCreators(){
        return $this->creators;
    }

    public function add($creator){

        if(!is_callable($creator)){
            throw new UnexpectedValueException('Creator has to be callable');
        }

        $this->creators[] = $creator;
        return $this;

    }

    /**
     * @brief Registers a closure as an action creator on resource $resource.
     *        This method is triggered on every existing item in an collection
     *        or outside of it
     *
     * @param mixed $resource Some resource in your application
     * @param callable $creator A closure which creates the action
     * @return void
     **/
    public function onItem($resource, callable $creator){

        $resources = is_array($resource) ? $resource : [$resource];

        foreach($resources as $resource){

            $resourceTypeId = $this->identifier->identifyItem($resource);

            if(!isset($this->itemCreators[$resourceTypeId])){
                $this->itemCreators[$resourceTypeId] = [];
            }

            $this->itemCreators[$resourceTypeId][] = $creator;

        }

    }

    /**
     * @brief Registers a closure as an action creator on resource $resource.
     *        This method is triggered mostly without an existing instance.
     *        Mostly you will pass the classname of the resource
     *
     * @param mixed $resource Some resource in your application
     * @param callable $creator A closure which creates the action
     * @return void
     **/
    public function onCollection($resource, callable $creator){

        $resources = is_array($resource) ? $resource : [$resource];

        foreach($resources as $resource){

            $resourceTypeId = $this->identifier->identifyItem($resource);

            if(!isset($this->collectionCreators[$resourceTypeId])){
                $this->collectionCreators[$resourceTypeId] = [];
            }

            $this->collectionCreators[$resourceTypeId][] = $creator;

        }

    }

    public function onType($type, callable $creator)
    {

        $resources = is_array($type) ? $type : [$type];

        foreach($resources as $resource){

            $resourceTypeId = $this->identifier->identifyItem($resource);

            if(!isset($this->typeCreators[$resourceTypeId])){
                $this->typeCreators[$resourceTypeId] = [];
            }

            $this->typeCreators[$resourceTypeId][] = $creator;

        }

    }

    public function forItem($resource, $context='default'){

        $actionGroup = $this->groupCreator->createGroup('default', $resource, $context);

        $resourceTypeId = $this->identifier->identifyItem($resource);

        if(isset($this->itemCreators[$resourceTypeId])){

            $user = $this->currentUserProvider->current();

            foreach($this->itemCreators[$resourceTypeId] as $creator){
                $creator($actionGroup, $user, $resource, $context);
            }
        }

        $this->markCurrentAction($actionGroup);

        if($context != 'default'){
            return $actionGroup->filtered($context);
        }
        return $actionGroup;

    }

    public function forCollection($resource, $context='default'){

        $actionGroup = $this->groupCreator->createGroup('default',$resource, $context);

        $resourceTypeId = $this->identifier->identifyCollection($resource);

        if(isset($this->collectionCreators[$resourceTypeId])){

            $user = $this->currentUserProvider->current();

            foreach($this->collectionCreators[$resourceTypeId] as $creator){
                $creator($actionGroup, $user, $resource, $context);
            }
        }

        $this->markCurrentAction($actionGroup);

        if($context != 'default'){
            return $actionGroup->filtered($context);
        }

        return $actionGroup;

    }

    public function forType($resource, $context='default'){

        $actionGroup = $this->groupCreator->createGroup('default', $resource, $context);

        $resourceTypeId = $this->identifier->identifyItem($resource);

        if(isset($this->typeCreators[$resourceTypeId])){

            $user = $this->currentUserProvider->current();

            foreach($this->typeCreators[$resourceTypeId] as $creator){
                $creator($actionGroup, $user, $context);
            }
        }

        $this->markCurrentAction($actionGroup);

        if($context != 'default'){
            return $actionGroup->filtered($context);
        }

        return $actionGroup;

    }

    public function get($user, $resource, $context='default'){

        $actionGroup = $this->groupCreator->createGroup('default',$resource, $context);

        foreach($this->creators as $creator){
            $creator($actionGroup, $user, $resource, $context);
        }
        return $actionGroup;

    }

    public function providerCurrentActionName(callable $provider)
    {
        $this->currentActionNameProvider = $provider;
        return $this;
    }

    protected function markCurrentAction(Group $group)
    {
        if (!$this->currentActionNameProvider) {
            return;
        }

        foreach ($group as $action) {
            if ($action instanceof Group) {
                $this->markCurrentAction($action);
                continue;
            }
            $this->markIfCurrent($action);
        }
    }

    protected function markIfCurrent(Action $action)
    {
        if (!$this->currentActionNameProvider) {
            return;
        }

        $currentActionName = call_user_func($this->currentActionNameProvider);

        if ($currentActionName == $action->getName()) {
            $action->setActive(true);
        }
    }

}

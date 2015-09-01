<?php namespace Cmsable\Resource;

use Illuminate\Contracts\Container\Container;
use Cmsable\Resource\Contracts\Detector;
use Cmsable\Resource\Contracts\ClassFinder;
use Cmsable\Resource\Contracts\Mapper as MapperContract;
use Cmsable\Resource\Contracts\ResourceForm;
use Cmsable\Resource\Contracts\Distributor as DistributorContract;

class Distributor implements DistributorContract
{

    use ResourceBus;

    protected $bus;

    protected $mapper;

    protected $finder;

    protected $detector;

    protected $container;

    protected $currentResource;

    public function __construct(MapperContract $mapper, ClassFinder $finder,
                                Detector $detector, Container $container)
    {
        $this->mapper = $mapper;
        $this->detector = $detector;
        $this->finder = $finder;
        $this->container = $container;
    }

    public function forwardResourceForm(ResourceForm $form)
    {

    }

    public function form($model=null, $resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if (!$formClass = $this->formClass($resource)) {
            return '';
        }

        return $this->makeForm($resource, $formClass, $model);

    }

    public function hasForm($resource=null)
    {
        return (bool)$this->formClass($resource);
    }

    public function searchForm($resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if (!$formClass = $this->searchFormClass($resource)) {
            return '';
        }

        return $this->makeSearchForm($resource, $formClass);

    }

    public function hasSearchForm($resource=null)
    {
        return (bool)$this->searchFormClass($resource);
    }

    public function validator($resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if ($class = $this->mapper->validatorClass($resource)) {
            return $this->makeValidator($resource, $class);
        }

        $modelClass = class_basename($this->modelClass($resource));

        if (!$class = $this->finder->validatorClass($resource, $modelClass)) {
            return;
        }

        return $this->makeValidator($resource, $class);

    }

    public function rules($resource=null)
    {

        if ($validator = $this->validator($resource)) {
            return $validator->rules();
        }

    }

    public function model($id, $resource=null)
    {
        
    }

    public function modelClass($resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if ($modelClass = $this->mapper->modelClass($resource)) {
            return $modelClass;
        }

        if ($modelClass = $this->finder->modelClass($resource)) {
            return $modelClass;
        }

    }

    public function formClass($resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if ($formClass = $this->mapper->formClass($resource)) {
            return $formClass;
        }

        $modelClass = class_basename($this->modelClass($resource));

        if ($formClass = $this->finder->formClass($resource, $modelClass)) {
            return $formClass;
        }

        return '';
    }

    public function searchFormClass($resource=null)
    {
        $resource = $this->passedOrCurrent($resource);

        if ($formClass = $this->mapper->searchFormClass($resource)) {
            return $formClass;
        }

        $modelClass = class_basename($this->modelClass($resource));

        if ($formClass = $this->finder->searchFormClass($resource, $modelClass)) {
            return $formClass;
        }

        return '';
    }

    public function getCurrentResource()
    {
        if (!$this->currentResource) {
            $request = $this->container->make('request');
            $this->currentResource = $this->detector->resourceByRequest($request);
        }

        return $this->currentResource;
    }

    public function setCurrentResource($resource)
    {
        $this->currentResource = $resource;
    }

    protected function passedOrCurrent($resource)
    {
        return $resource ? $resource : $this->getCurrentResource();
    }

    protected function makeForm($resource, $class, $model)
    {
        $form = $this->container->make($class);

        if ($rules = $this->rules($resource)) {
            $form->getValidator()->setRules($rules);
        }

        if ($model) {
            $form->setModel($model);
        }

        $this->publish($resource, 'form.created', [$form]);

        return $form;
    }

    protected function makeSearchForm($resource, $class)
    {
        $form = $this->container->make($class);
        $this->publish($resource, 'search-form.created', [$form]);
        $form->addCssClass('search-form');
        return $form;
    }

    protected function makeValidator($resource, $class)
    {
        $validator = $this->container->make($class);
        $this->publish($resource, 'validator.created', [$validator]);
        return $validator;
    }

    protected function publish($resource, $event, array $params=[])
    {
        $eventName = $this->eventName("$resource.$event");
        $this->fire($eventName, $params);
    }

}
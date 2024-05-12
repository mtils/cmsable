<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ClassFinder;
use Cmsable\Resource\Contracts\Detector;
use Cmsable\Resource\Contracts\Distributor as DistributorContract;
use Cmsable\Resource\Contracts\Mapper as MapperContract;
use Cmsable\Resource\Contracts\Validator;
use Ems\Core\Patterns\SubscribableTrait;
use FormObject\Form;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class Distributor implements DistributorContract
{

    //use ResourceBus;
    use SubscribableTrait;

    protected $bus;

    protected $mapper;

    protected $finder;

    protected $detector;

    protected $container;

    protected $currentResource;

    protected $manualRules = [];

    public function __construct(MapperContract $mapper, ClassFinder $finder,
                                Detector $detector, Container $container)
    {
        $this->mapper = $mapper;
        $this->detector = $detector;
        $this->finder = $finder;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $model (optional)
     * @param string $resource (optional)
     * @return Form
     **/
    public function form($model=null, $resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if (!$formClass = $this->formClass($resource)) {
            return '';
        }

        return $this->makeForm($resource, $formClass, $model);

    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource (optional)
     * @return bool
     **/
    public function hasForm($resource=null)
    {
        return (bool)$this->formClass($resource);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource (optional)
     * @return Form
     **/
    public function searchForm($resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if (!$formClass = $this->searchFormClass($resource)) {
            return '';
        }

        return $this->makeSearchForm($resource, $formClass);

    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource (optional)
     * @return bool
     **/
    public function hasSearchForm($resource=null)
    {
        return (bool)$this->searchFormClass($resource);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource (optional)
     * @return Validator
     **/
    public function validator($resource=null)
    {

        $resource = $this->passedOrCurrent($resource);

        if ($class = $this->mapper->validatorClass($resource)) {
            return $this->makeValidator($resource, $class);
        }

        $modelClass = class_basename($this->modelClass($resource));

        if ($class = $this->finder->validatorClass($resource, $modelClass)) {
            return $this->makeValidator($resource, $class);;
        }

        $class = 'Cmsable\Resource\GenericResourceValidator';

        if ($rules = $this->getManualRules($resource)) {
            $validator = $this->makeValidator($resource, $class);
            return $validator->setRules($rules);
        }

        // Support for rules inside forms
        if (!$this->hasForm($resource)) {
            throw new RuntimeException('No validator for resource $resource found');
        }


        if ($rules = $this->formRules($this->form(null, $resource))) {
             $validator = $this->makeValidator($resource, $class);
             return $validator->setRules($rules);

        }

        throw new RuntimeException('No validator for resource $resource found');

    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource (optional)
     * @return array
     **/
    public function rules($resource=null)
    {

        if ($validator = $this->validator($resource)) {
            return $validator->rules();
        }


    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource
     * @param array $rules
     * @return self
     **/
    public function setRules($resource, array $rules)
    {
        $this->manualRules[$resource] = $rules;
        return $this;
    }

    /**
     * This returns the model class for $resource. Caution: Not in interface
     *
     * @param string $resource (optional)
     * @return string
     **/
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

    /**
     * This returns the form class for $resource. Caution: Not in interface
     *
     * @param string $resource (optional)
     * @return string
     **/
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

    /**
     * This returns the search form class for $resource. Caution: Not in interface
     *
     * @param string $resource (optional)
     * @return string
     **/
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

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function getCurrentResource()
    {
        if (!$this->currentResource) {
            $request = $this->container->make('request');
            $this->currentResource = $this->detector->resourceByRequest($request);
        }

        return $this->currentResource;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resource
     * @return self
     **/
    public function setCurrentResource($resource)
    {
        $this->currentResource = $resource;
        return $this;
    }

    /**
     * Returns the passed resource if passed, else the current
     *
     * @param string $resource
     * @return $resource
     **/
    protected function passedOrCurrent($resource)
    {
        return $resource ? $resource : $this->getCurrentResource();
    }

    /**
     * Creates the form and fires an event
     *
     * @param string $resource
     * @param string $class
     * @param mixed $model
     * @return Form
     **/
    protected function makeForm($resource, $class, $model)
    {
        $form = $this->container->make($class);

        if (!$this->formRules($form) && $rules = $this->rules($resource)) {
            $form->getValidator()->setRules($rules);
        }

        if ($model) {
            $form->setModel($model);
        }

        $this->publish($resource, 'form.created', [$form]);

        return $form;
    }

    /**
     * Creates the search form and fires an event
     *
     * @param string $resource
     * @param string $class
     * @return Form
     **/
    protected function makeSearchForm($resource, $class)
    {
        $form = $this->container->make($class);
        $this->publish($resource, 'search-form.created', [$form]);
        $form->addCssClass('search-form');
        return $form;
    }

    /**
     * Creates the validator and fires an event
     *
     * @param string $resource
     * @param string $class
     * @return Validator
     **/
    protected function makeValidator($resource, $class)
    {
        $validator = $this->container->make($class);

        if (method_exists($validator, 'setResourceName')) {
            $validator->setResourceName($resource);
        }
        $this->publish($resource, 'validator.created', [$validator]);
        return $validator;
    }

    /**
     * Returns the manual rules that has been set for $resource
     *
     * @param string $resource
     * @return bool
     **/
    protected function getManualRules($resource)
    {
        if (!isset($this->manualRules[$resource])) {
            return [];
        }
        return $this->manualRules[$resource];
    }

    /**
     * Publishes an event on the event bus
     *
     * @param string $resource
     * @param string $event
     * @param array $params
     **/
    protected function publish($resource, $event, array $params=[])
    {
        $eventName = $this->eventName("$resource.$event");

        $this->callOnListeners($eventName, $params);
    }

    protected function formRules(Form $form)
    {
        if (property_exists($form, 'validationRules')) {
            return $form->validationRules;
        }

        if (method_exists($form, 'validationRules')) {
            return $form->validationRules();
        }

    }

    protected function eventName($name)
    {
        return "resource::$name";
    }

}

<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\TreeRepository;
use Signal\NamedEvent\BusHolderTrait;
use Illuminate\Database\Eloquent\Model;
use FormObject\Form;

abstract class BeeTreeRepository implements TreeRepository
{

    use ResourceBus;

    protected $model;

    abstract public function getModel();

    abstract public function resourceName();

    /**
     * @var callable
     **/
    protected $attributeFilter;

    /**
     * Return the resource with id $id. Throw an exception if resource not found
     *
     * @return mixed The resource
     **/
    public function find($id)
    {
        $this->fire($this->event('find'), []);
        $model = $this->model()->get($id);
        $this->fire($this->event('found'), [$model]);
        return $model;
    }

    /**
     * Instantiate a new resource and fill it with the attributes
     *
     * @param array $attributes
     * @return mixed The instantiated resource
     **/
    public function make(array $attributes=[])
    {
        $model = $this->model()->make($attributes);
        $this->fire($this->event('make'), [$model]);
        return $model;
    }

    /**
     * Construct a node (new $NodeClass()) (Doesn't save the node)
     * 
     * @param array $attributes (optional)
     * @param mixed \Beetree\Contracts\Node
     * @return mixed the created child
     **/
    public function makeChild(array $attributes=[], $parent)
    {
        $child = $this->model()->makeChild($attributes, $parent);
        $this->fire($this->event('make'), [$child]);
        return $child;
    }

    /**
     * Create a new resource by the given attributes
     *
     * @param array $attributes
     * @return mixed The created resource
     **/
    public function store(array $attributes)
    {

        $this->validate($attributes, 'store');

        $filtered = $this->toModelAttributes($this->make([]), $attributes);

        $this->fire($this->event('storing'), [$attributes]);

        $model = $this->model()->createRoot($filtered);

        $this->fire($this->event('stored'), [$model]);

        return $model;

    }

    /**
     * Update the resource with id $id with new attributes $attributes
     * Return the resource after updating it. Must throw an exception if not found
     *
     * @param mixed $model
     * @param array $newAttributes
     * @return mixed The updated resource
     **/
    public function update($model, array $newAttributes)
    {

        $this->validate($newAttributes, 'update');

        $this->fillModel($model, $newAttributes);

        $this->fire($this->event('updating'), [$model]);

        $this->model()->savePayload($model);

        $this->fire($this->event('updated'), [$model]);

        return $model;
    }

    /**
     * Delete the resource with id $id. Throw an exception if not found.
     *
     * @param mixed $model
     * @return mixed The deleted resource
     **/
    public function delete($model)
    {
        $this->fire($this->event('destroying'), [$model]);
        $this->model()->remove($model);
        $this->fire($this->event('destroyed'), [$model]);
        return $model;
    }

    /**
     * Create a new model as a child of $parentModel
     *
     * @param array $attributes
     * @param mixed $parentModel
     * @param int $position (optional, defaults to last)
     * @return mixed The created resource
     **/
    public function storeAsChildOf(array $attributes, $parentModel, $position=null)
    {

        $this->validate($attributes, 'store');

        $filtered = $this->toModelAttributes($this->make([]), $attributes);

        $this->fire($this->event('storing'), [$attributes, $parentModel]);

        if (!$position) {
            $model = $this->model()->createChildOf($filtered, $parentModel);
        } else {
            $model = $this->model()->createAt($filtered, $parentModel, $position);
        }

        $this->fire($this->event('stored'), [$model]);

        return $model;
    }

    /**
     * Move the $movedNode inside $newParent to position $position
     *
     * @param \BeeTree\Contracts\Sortable $movedNode
     * @param \BeeTree\Contracts\Sortable $newParent
     * @param int $position
     * @return self
     **/
    public function moveToParent($movedNode, $newParent, $position=null)
    {

        $this->fire($this->event('moving'), [$movedNode, $newParent, $position]);

        $this->model()->placeAt($movedNode, $newParent, $position);

        if (!$position) {
            $this->model()->makeChildOf($movedNode, $parentModel);
        } else {
            $this->model()->placeAt($movedNode, $parentModel, $position);
        }

        $this->fire($this->event('moved'), [$movedNode, $newParent, $position]);

        return $this;
    }

    public function filterAttributesBy(callable $callable)
    {
        $this->attributeFilter = $callable;
    }

    protected function validate(array $attributes, $action='update'){}

    protected function getAttributeFilter()
    {
        return function($key, $value){ return is_scalar($value); };
    }

    protected function toModelAttributes($model, $attributes)
    {

        $filtered = [];
        $filter = $this->getAttributeFilter();

        foreach ($attributes as $key=>$value) {

            if (!$filter($key, $value)) {
                continue;
            }

            $filtered[$key] = $value;
        }
        return $filtered;
    }

    protected function fillModel(Model $model, array $attributes)
    {
        $filtered = $this->toModelAttributes($model, $attributes);
        $model->fill($filtered);
    }

    protected function event($name)
    {
        return $this->eventName($this->resourceName() . ".$name");
    }

    protected final function model()
    {
        if (!$this->model) {
            $this->model = $this->getModel();
        }

        return $this->model;
    }

}
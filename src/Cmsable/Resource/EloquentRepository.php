<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class EloquentRepository implements Repository
{

    use ResourceBus;

    abstract public function getModel();

    abstract public function resourceName();

    /**
     * @var callable
     **/
    protected $attributeFilter;

    /**
     * {@inheritdoc}
     *
     * @return mixed The resource
     **/
    public function find($id)
    {
        $query = $this->getModel()->newQuery();
        $this->fire($this->event('find'), [$query]);

        if (!$model = $query->find($id)) {
            return;
        }

        $this->fire($this->event('found'), [$model]);

        return $model;
    }

    /**
     * Find and throw an exception if model not found
     *
     * @return mixed The resource
     **/
    public function findOrFail($id)
    {
        if ($model = $this->find($id)) {
            return $model;
        }
        throw (new ModelNotFoundException)->setModel(get_class($this->getModel()));
    }

    /**
     * Instantiate a new resource and fill it with the attributes
     *
     * @param array $attributes
     * @return mixed The instantiated resource
     **/
    public function make(array $attributes=[])
    {
        $model = $this->getModel()->newInstance($attributes);
        $this->fire($this->event('make'), [$model]);
        return $model;
    }

    /**
     * Create a new resource by the given attributes
     *
     * @param array $attributes
     * @return mixed The created resource
     **/
    public function store(array $attributes)
    {
        $model = $this->make([]);

        $this->validate($attributes, 'store');

        $this->fillModel($model, $attributes);
        $this->fire($this->event('storing'), [$model, $attributes]);
        $model->save();
        $this->fire($this->event('stored'), [$model, $attributes]);
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

        $this->fire($this->event('updating'), [$model, $newAttributes]);

        $model->save();

        $this->fire($this->event('updated'), [$model, $newAttributes]);

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
        $model->delete();
        $this->fire($this->event('destroyed'), [$model]);
        return $model;
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

}
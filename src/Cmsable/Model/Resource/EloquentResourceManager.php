<?php namespace Cmsable\Model\Resource;

use Signal\NamedEvent\BusHolderTrait;
use Illuminate\Database\Eloquent\Model;
use FormObject\Form;

abstract class EloquentResourceManager implements ManagerInterface
{

    use ResourceBus;

    abstract public function getModel();

    abstract public function resourceName();

    /**
     * Return the resource with id $id. Throw an exception if resource not found
     *
     * @return mixed The resource
     **/
    public function find($id)
    {
        $query = $this->getModel()->newQuery();
        $this->fire($this->event('find'), [$query]);
        $model = $query->find($id);
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
    public function store(array $attributes=[])
    {
        $model = $this->make([]);

        $this->validate($attributes, 'store');

        $this->fillModel($model, $attributes);
        $this->fire($this->event('storing'), [$model]);
        $model->save();
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

        $model->save();

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
        $model->delete();
        $this->fire($this->event('destroyed'), [$model]);
        return $model;
    }

    protected function validate(array $attributes, $action='update')
    {
        
    }

    protected function toModelAttributes($model, $attributes)
    {

        $filtered = [];

        foreach ($attributes as $key=>$value) {

            if (!is_scalar($value)) {
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
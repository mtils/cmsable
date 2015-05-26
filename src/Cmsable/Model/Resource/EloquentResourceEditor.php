<?php namespace Cmsable\Model\Resource;

use Signal\NamedEvent\BusHolderTrait;
use Illuminate\Database\Eloquent\Model;
use FormObject\Form;

abstract class EloquentResourceEditor implements EditorInterface
{

    use ResourceBus;

    abstract public function getModel();

    abstract public function getResourceName();

    /**
     * Return the resource with id $id. Throw an exception if resource not found
     *
     * @return mixed The resource
     **/
    public function findOrFail($id)
    {
        $query = $this->getModel()->newQuery();
        $this->fire($this->event('find'), [$query]);
        $model = $query->findOrFail($id);
        $this->fire($this->event('found'), [$model]);
        return $model;
    }

    /**
     * Instantiate a new resource  and fill it with the attributes
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
        $this->fire($this->event('store'), [$model, $attributes]);

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
     * @param mixed $id
     * @param array $newAttributes
     * @return mixed The updated resource
     **/
    public function update($id, array $newAttributes)
    {
        $model = $this->findOrFail($id);

        $this->fire($this->event('update'), [$model, $newAttributes]);

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
     * @param mixed $id
     * @return mixed The deleted resource
     **/
    public function delete($id)
    {
        $model = $this->findOrFail($id);
        $this->fire($this->event('destroy'), [$model]);
        $this->fire($this->event('destroying'), [$model]);
        $model->delete();
        $this->fire($this->event('destroyed'), [$model]);
        return $model;
    }

    /**
     * Create a model, the form, assign the model to the form and return it. If
     * validation failes or something similar throw an exception
     *
     * @param array $attributes
     * @return \FormObject\Form
     **/
    public function editNew(array $attributes=[])
    {
        $model = $this->make($attributes);
        $this->fire($this->event('create'), [$model]);
        $form = $this->getModelForm($model, $attributes);
        $this->fillForm($form, $model);
        $this->fire($this->event('form-filled'), [$form, $model]);
        $form->setModel($model);
        return $form;

    }

    /**
     * Create a form for model $model, assign the model to the form and return
     * the form
     *
     * @param mixed $id
     * @return \FormObject\Form
     **/
    public function edit($id)
    {
        $model = $this->findOrFail($id);
        $this->fire($this->event('edit'), [$model]);
        $form = $this->getModelForm($model, []);
        $form->setModel($model);
        $this->fillForm($form, $model);
        $this->fire($this->event('form-filled'), [$form, $model]);
        return $form;
    }

    protected function fillForm(Form $form, Model $model)
    {
        $form->fillByArray($model->toArray());
    }

    protected function validate(array $attributes, $action='update')
    {
        
    }

    protected function toModelAttributes($model, $attributes)
    {

        $filtered = [];

        foreach ($attributes as $key=>$value) {

            // tokens, _method...
            if (starts_with($key,'_')) {
                continue;
            }

            // FormObject nested stuff
            if (str_contains($key, ['__','-'])) {
                continue;
            }

            if (ends_with($key, '_confirmation')) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    protected function getValidatorInstance($action='update')
    {

        $rules = $this->findRules();

        $this->fire($this->event('rules-created'), [&$rules, $action]);

        dd($rules);

        return \Validator::make([], $rules);
    }

    protected function findRules()
    {
        if (isset($this->validationRules)) {
            return $this->validationRules;
        }

        if (method_exists($this, 'validationRules')) {
            return $this->validationRules();
        }

        $form = $this->newForm([]);

        if (!$validator = $form->getValidationBroker()->getValidator()) {
            return [];
        }

        if (!method_exists($validator, 'getRules')) {
            return [];
        }

        return $validator->getRules();

    }

    protected function getModelForm(Model $model, array $attributes)
    {
        $form = $this->newForm($attributes);
        $this->fire($this->event('form-created'), [$model, $form]);
        return $form;
    }

    protected function fillModel(Model $model, array $attributes)
    {
        $filtered = $this->toModelAttributes($model, $attributes);
        $model->fill($filtered);
    }

    protected function event($name)
    {
        return $this->getResourceName() . ".$name";
    }

}
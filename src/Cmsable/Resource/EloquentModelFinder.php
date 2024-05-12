<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ModelFinder;
use Illuminate\Database\Eloquent\Model;

class EloquentModelFinder implements ModelFinder
{

    protected $modelClass;

    protected $model;

    public function modelClass()
    {
        return $this->modelClass;
    }

    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    public function find($id)
    {
        return $this->model($this->modelClass, $id)->find($id);
    }

    public function model($modelClass, $id)
    {
        if (!$this->model) {
            $class = $this->modelClass();
            $this->model = new $class();
        }

        return $this->model;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->modelClass = get_class($model);
        return $this;
    }

}
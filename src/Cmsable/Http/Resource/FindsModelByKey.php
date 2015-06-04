<?php namespace Cmsable\Http\Resource;

use OutOfBoundsException;
use RuntimeException;
use BadMethodCallException;
use Cmsable\Resource\Contracts\ModelFinder;
use Cmsable\Resource\EloquentModelFinder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Database\Eloquent\Model;

trait FindsModelByKey
{

    use UsesCurrentResource;

    protected $modelFinder;

    protected $model;

    public function findModel($key)
    {
        if ($this->model && $this->modelHasKey($this->model, $key)) {
            return $this->model;
        }

        if ($this->model && !$this->modelHasKey($this->model, $key)) {
            throw new BadMethodCallException("Different model already added");
        }

        if (!$model = $this->getModelFromStore($key)) {
            $this->handleModelNotFound($key);
        }

        if (!$this->userCanAccess($model)) {
            $this->handleAccessDenied($model);
        }

        $this->model = $model;

        return $this->model;

    }

    public function model()
    {
        if (!$this->model) {
            $this->model = $this->findModel($this->getModelKeyFromRequest());
        }

        return $this->model;

    }

    /**
     * @return \Cmsable\Resource\Contracts\ModelFinder
     **/
    public function modelFinder()
    {

        if (!$this->modelFinder) {
            $this->modelFinder = new EloquentModelFinder();
            $this->modelFinder->setModelClass($this->modelClass());
        }

        return $this->modelFinder;
    }

    public function setModelFinder(ModelFinder $finder)
    {
        $this->modelFinder = $finder;
        return $this;
    }

    public function getModelKeyFromRequest()
    {
        if (!$route = $this->route()) {
            throw new OutOfBoundsException("Request has no route");
        }

        $parameterNames = $route->parameterNames();
        $parameters = $route->parameters();

        return $parameters[$parameterNames[0]];

    }

    /**
     * Check if use can access model
     *
     * @param mixed $model
     * @return bool
     **/
    protected function userCanAccess($model)
    {
        return true;
    }

    protected function handleModelNotFound($key)
    {
        throw new NotFoundHttpException();
    }

    protected function handleAccessDenied($model)
    {
        throw new AccessDeniedHttpException();
    }

    protected function getModelFromStore($id)
    {
        return $this->modelFinder()->find($key);
    }

    protected function modelHasKey($model, $key)
    {
        if ($model instanceof Model) {
            return ($model->getKey() == $key);
        }

        throw new RuntimeException("Cant determine if model has key $key");
    }

}
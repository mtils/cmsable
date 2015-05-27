<?php namespace Cmsable\Http\Resource;

use OutOfBoundsException;
use Cmsable\Http\Contracts\DecoratesRequest;
use Cmsable\Http\ReplicateRequest;
use Cmsable\Resource\Contracts\ReceivesResourceMapper;

class SaveRequest extends FormRequest
{

    use FindsModelByKey;

    protected $casted;

    public function casted($key=null, $default=null)
    {
        if ($this->casted === null) {
            $this->casted = $this->performCasting($this->getInputSource()->all() + $this->query->all());
        }

        if ($key != null) {
            return array_get($this->casted, $key, $default);
        }

        return $this->casted;
    }

    protected function performCasting(array $parameters)
    {
        $casted = $this->caster()->castInput($parameters);
        $this->fireActionEvent($casted);
        return $casted;
    }

    protected function caster()
    {
        return $this->container->make('XType\Casting\Contracts\InputCaster');
    }

    protected function getModelFromStore($key)
    {

        $model = $this->modelFinder()->find($key);

        if ($model) {
            $this->fireAction('update', $model);
        }

        return $model;
    }

    protected function fireActionEvent($params)
    {
        $action = $this->getResourceActionName();

        if ($action == 'update') {
            $this->fireAction($action, [$this->model(), $params]);
            return;
        }

        $this->fireAction($action, [$params]);

    }

    protected function getResourceActionName()
    {
        if (!$route = $this->route()) {
            throw new OutOfBoundsException("Request has no route, no chance to get the action");
        }
        return last(explode('.', $route->getName()));
    }

}
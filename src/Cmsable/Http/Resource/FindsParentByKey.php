<?php namespace Cmsable\Http\Resource;


use BadMethodCallException;
use OutOfBoundsException;

trait FindsParentByKey
{

    use FindsModelByKey;

    protected $parent;

    public function findParent($key)
    {

        if ($this->parent && $this->modelHasKey($this->parent, $key)) {
            return $this->parent;
        }

        if ($this->parent && !$this->modelHasKey($this->parent, $key)) {
            throw new BadMethodCallException("Different parent already added");
        }

        if (!$parent = $this->getParentFromStore($key)) {
            $this->handleParentNotFound($key);
        }

        if (!$this->userCanAccess($parent)) {
            $this->handleAccessDenied($parent);
        }

        $this->parent = $parent;

        return $this;

    }

    public function parent()
    {
        if (!$this->parent) {
            $this->parent = $this->findParent($this->getParentKeyFromRequest());
        }
        return $this->parent;
    }

    public function getModelKeyFromRequest()
    {
        if (!$route = $this->route()) {
            throw new OutOfBoundsException("Request has no route");
        }

        $parameterNames = $route->parameterNames();
        $parameters = $route->parameters();

        return $parameters[$parameterNames[1]];

    }

    public function getParentKeyFromRequest()
    {
        if (!$route = $this->route()) {
            throw new OutOfBoundsException("Request has no route");
        }

        $parameterNames = $route->parameterNames();
        $parameters = $route->parameters();

        return $parameters[$parameterNames[0]];

    }

    protected function handleParentNotFound($key)
    {
        return $this->handleModelNotFound($key);
    }

    protected function getParentFromStore($id)
    {
        return $this->modelFinder()->find($key);
    }

}
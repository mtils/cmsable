<?php namespace Cmsable\Http\Resource;

use Illuminate\Foundation\Http\FormRequest;
use Cmsable\Resource\ResourceBus;

abstract class Request extends FormRequest
{

    use ResourceBus;

    protected function getValidatorInstance()
    {
        $factory = $this->container->make('Illuminate\Validation\Factory');

        if (method_exists($this, 'validator'))
        {
            return $this->container->call([$this, 'validator'], compact('factory'));
        }

        $validator = $factory->make(
            $this->all(), $this->getRulesByCall(), $this->messages()
        );

        $validator->setAttributeNames($this->attributes());

        return $validator;

    }

    public function authorize()
    {
        return true;
    }

    protected function getRulesByCall()
    {

        $rules = $this->container->call([$this, 'rules']);

        $resource = $this->getResourceName();

        $this->fire("$resource.rules-setted", [&$rules]);

        return $rules;
    }


    protected function getResourceName()
    {
        $mapper = $this->container->make('Cmsable\Resource\Contracts\ResourceMapper');
        return $mapper->resourceByRequest($this);
    }

}
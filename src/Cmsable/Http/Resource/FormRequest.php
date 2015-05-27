<?php namespace Cmsable\Http\Resource;

use Cmsable\Http\Contracts\DecoratesRequest;
use Cmsable\Http\ReplicateRequest;
use Cmsable\Resource\Contracts\ReceivesResourceMapper;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;

abstract class FormRequest extends BaseFormRequest implements ReceivesResourceMapper
{
    // use UsesCurrentResource // Use this trait to use this class;

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
        $this->fireAction("rules-setted", [&$rules]);
        return $rules;
    }

}
<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\Validator;
use App;
use Cmsable\Support\ReceivesContainerWhenResolved;
use Cmsable\Support\HoldsContainer;

abstract class ResourceValidator implements Validator, ReceivesContainerWhenResolved
{

    use ResourceBus;
    use HoldsContainer;

    protected $rules = [];

    public function rules()
    {
        return $this->rules;
    }

    /**
     * Validate the data. If validation failes, throw a exception
     * If a model is passed as the second parameter, parse the rules
     * to match the model. If no model is passed, considerate it as
     * a new model
     *
     * @param array $data The (request) data
     * @param mixed $model (optional)
     * @return bool
     * @throw \Illuminate\Contracts\Validation\ValidationException
     **/
    public function validateOrFail(array $data, $model=null)
    {
        $rules = $this->rules();
        $parsedRules = $this->parseRules($rules, $data, $model);
    }

    protected function parseRules(array $rules, array $data, $model=null)
    {
    
    }

}
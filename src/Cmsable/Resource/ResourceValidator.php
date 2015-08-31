<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\Validator;
use App;
use Cmsable\Support\ReceivesContainerWhenResolved;
use Cmsable\Resource\Contracts\ReceivesDistributorWhenResolved;
use Cmsable\Support\HoldsContainer;
use Illuminate\Contracts\Validation\ValidationException;

abstract class ResourceValidator implements Validator, ReceivesContainerWhenResolved,
                                            ReceivesDistributorWhenResolved
{

    use HoldsContainer;
    use UsesCurrentResource;

    protected $rules = [];

    private $extendedRules;

    public final function rules()
    {
        if ($this->extendedRules !== null) {
            return $this->extendedRules;
        }

        $rules = $this->buildRules();

        $this->publish('validation-rules.setted', [&$rules]);

        $this->extendedRules = $rules;

        return $this->extendedRules;

    }

    public function buildRules()
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

        $this->publish('validating', [$this, $data, $model]);

        $rules = $this->rules();

        $parsedRules = $this->parseRules($rules, $data, $model);

        $this->publish('validation-rules.parsed', [&$parsedRules]);

        $validator = $this->getValidatorInstance($parsedRules, $data, $model);

        $validator->setAttributeNames($this->customAttributes());

        if ($this->validate($validator)) {
            return true;
        }

        throw new ValidationException($validator);

    }

    protected function validate($validator)
    {
        return $validator->passes();
    }

    protected function parseRules(array $rules, array $data, $model=null)
    {
        return $rules;
    }

    /**
     * Get the validator instance to perform the actual validation
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function getValidatorInstance($rules, $data, $model=null)
    {
        $factory = $this->container->make('Illuminate\Validation\Factory');

        if (method_exists($this, 'validatorInstance'))
        {
            return $this->container->call(
                [$this, 'validatorInstance'],
                compact('factory')
            );
        }

        return $factory->make(
            $data, $rules, $this->customMessages(), $this->customAttributes()
        );
    }

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function customMessages()
    {
        return [];
    }

    /**
     * Set custom attributes for validator errors.
     *
     * @return array
     */
    public function customAttributes()
    {
        if (!$form = $this->distributor->form(null, $this->resourceName())){
            return [];
        }
        return $form->getValidator()->buildAttributeNames($form);
    }

    private function getAndFireRules()
    {
    }

}
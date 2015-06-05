<?php namespace Cmsable\Resource\Contracts;

interface Validator
{

    /**
     * Returns the base rules. This are the minimum unparsed rules
     * Often you need a different set of rules if a model exists
     * (unique) or not or you build in an resizable array many
     * rules from one (addresses.*.street). The base rules contain
     * the wildcards, not the unique constraints,..
     *
     * @return array
     **/
    public function rules();

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
    public function validateOrFail(array $data, $model=null);

}

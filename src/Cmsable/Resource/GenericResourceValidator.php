<?php


namespace Cmsable\Resource;

class GenericResourceValidator extends ResourceValidator
{

    /**
     * Manually set the rules
     *
     * @param array $rules
     * @return self
     **/
    public function setRules(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

}
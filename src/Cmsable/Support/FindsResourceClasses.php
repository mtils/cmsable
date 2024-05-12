<?php namespace Cmsable\Support;


use Ems\Contracts\Core\Type;
use Illuminate\Support\Pluralizer;

trait FindsResourceClasses
{
    use FindsClasses;

    protected function className($resourceName)
    {
        return $this->camelCase(Pluralizer::singular($resourceName));
    }

    protected function resourceName($className)
    {
        return Type::snake_case(Pluralizer::plural(class_basename($className)), '-');
    }

}

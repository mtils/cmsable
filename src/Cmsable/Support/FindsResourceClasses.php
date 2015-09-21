<?php namespace Cmsable\Support;

use Signal\Support\FindsClasses;
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
        return snake_case(Pluralizer::plural(class_basename($className)), '-');
    }

}

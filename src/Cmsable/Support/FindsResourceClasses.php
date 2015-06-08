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

}

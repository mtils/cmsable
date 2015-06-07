<?php namespace Cmsable\Support;

use Illuminate\Contracts\Container\Container;

trait HoldsContainer
{

    protected $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

}
<?php namespace Cmsable\Support;

trait HoldsContainer
{

    protected $container;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

}
<?php namespace Cmsable\Support;

use Illuminate\Contracts\Container\Container;

interface ReceivesContainerWhenResolved
{

    public function setContainer(Container $container);

}
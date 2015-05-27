<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ResourceForm;

class Distributor
{

    protected $bus;

    public function __construct(Bus $bus)
    {
        $this->bus = $bus;
    }

    public function forwardResourceForm(ResourceForm $form)
    {

    }

}
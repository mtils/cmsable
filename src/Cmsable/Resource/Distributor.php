<?php namespace Cmsable\Resource;

use Cmsable\Resource\Contracts\ResourceForm;
use Cmsable\Model\Resource\Bus;

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
<?php namespace Cmsable\Resource\Contracts;

interface ReceivesDistributorWhenResolved
{
    public function setResourceDistributor(Distributor $distributor);
}
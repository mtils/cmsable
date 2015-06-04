<?php namespace Cmsable\Resource\Contracts;

interface OrderedTreeRepository extends TreeRepository
{

    public function storeBefore(array $attributes, $newSuccessor);

}
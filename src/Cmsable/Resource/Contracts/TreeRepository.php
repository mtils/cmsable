<?php namespace Cmsable\Resource\Contracts;

use Cmsable\Resource\Contracts\ModelFinder;

/** 
 * A Resource Repository a repository specialized to edit RESTful
 * resources.
 **/
interface TreeRepository extends Repository
{

    /**
     * Create a new model as a child of $parentModel
     *
     * @param array $attributes
     * @param mixed $parentModel
     * @param int $position (optional, defaults to last)
     * @return mixed The created resource
     **/
    public function storeAsChildOf(array $attributes, $parentModel, $position=null);

    /**
     * Save the $movedModel as a child of $newParent
     *
     * @param mixed $movedModel
     * @param mixed $newParent
     * @param int $position (optional, defaults to last)
     * @return mixed the updated resource
     **/
    public function moveToParent($movedModel, $newParent, $position=null);

}
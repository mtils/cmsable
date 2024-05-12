<?php namespace Cmsable\Resource\Contracts;

use Cmsable\Resource\Contracts\ModelFinder;

/**
 * A Resource Repository a repository specialized to edit RESTful
 * resources.
 **/
interface TreeRepository extends Repository
{

    /**
     * Construct a node (new $NodeClass()) (Doesn't save the node)
     *
     * @param array $attributes (optional)
     * @param mixed $parent
     * @return mixed the created child
     **/
    public function makeChild(array $attributes=[], $parent=null);

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
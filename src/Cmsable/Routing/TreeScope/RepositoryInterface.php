<?php namespace Cmsable\Routing\TreeScope;

interface RepositoryInterface{

    /**
     * Returns all available scopes
     *
     * @return \Traversable
     **/
    public function getAll();

    /**
     * Returns the scope with name $name
     *
     * @return TreeScope
     **@throws OutOfBoundsException If no scope with name $name was found
     */
    public function get($name);

    /**
     * Returns the scope by $pathPrefix
     *
     * @return TreeScope
     **@throws OutOfBoundsException If no scope with pathprefix $pathPrefix was found
     */
    public function getByPathPrefix($pathPrefix);

    /**
     * Returns the scope by modelRootId
     *
     * @return TreeScope
     **@throws OutOfBoundsException If no scope with root-id $rootId was found
     */
    public function getByModelRootId($rootId);

}
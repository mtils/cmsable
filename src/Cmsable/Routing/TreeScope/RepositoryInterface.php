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
     * @throws OutOfBoundsException If no scope with name $name was found
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function get($name);

    /**
     * Returns the scope by $pathPrefix
     *
     * @throws OutOfBoundsException If no scope with pathprefix $pathPrefix was found
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function getByPathPrefix($pathPrefix);

    /**
     * Returns the scope by modelRootId
     *
     * @throws OutOfBoundsException If no scope with root-id $rootId was found
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function getByModelRootId($rootId);

}
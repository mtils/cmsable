<?php namespace Cmsable\PageType;

interface RepositoryInterface{

    /**
    * @brief Return PageType by id
    * 
    * @param string $id
    * @return \Cmsable\PageType\PageType
    */
    public function get($id);

    /**
    * @brief Check if PageType with id $id exists
    * 
    * @param string $id
    * @return bool
    */
    public function has($id);

    /**
     * Return a pagetype by route name (if exists). The repository has to
     * add all PageType::getRouteNames() to an array and return the pagetypes
     * which registered itself for that route name
     *
     * @param string
     * @return PageType|null
     **/
    public function getByRouteName($routeName);

    /**
    * @brief Returns all PageType Instances within routeScope $routeScope
    * 
    * @param string $routeScope (optional)
    * @return \Traversable
    */
    public function all($routeScope='default');

    /**
    * @brief Returns all PageType Instances within routeScope $routeScope
    *        subKeyed by its category
    * 
    * @param string $routeScope (optional)
    * @return array
    */
    public function byCategory($routeScope='default');

    /**
    * @brief Return PageType-Category by name
    * 
    * @param string $name
    * @return \Cmsable\PageType\Category
    */
    public function getCategory($name);

    /**
    * @brief Returns all PageType Categories within routeScope $routeScope
    * 
    * @param string $routeScope (optional)
    * @return Traversable
    */
    public function getCategories($routeScope='default');
}
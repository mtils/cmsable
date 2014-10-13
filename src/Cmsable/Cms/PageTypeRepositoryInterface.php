<?php namespace Cmsable\Cms;

interface PageTypeRepositoryInterface{

    /**
    * @brief Return PageType by id
    * 
    * @param string $id
    * @return PageType
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
    * @return PageTypeCategory
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
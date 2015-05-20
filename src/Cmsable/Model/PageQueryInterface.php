<?php namespace Cmsable\Model;

/**
 * A page query allows to find pages by its properties including the virtual
 * ones in your pagetype configs.
 **/
interface PageQueryInterface
{

    /**
     * Apply a where clause to the query
     *
     * @param string $key The key name
     * @param mixed $operatorOrValue value if $value is null
     * @param mixed $value (optional)
     * @return self
     **/
    public function where($key, $operatorOrValue, $value=null);

    /**
     * Apply an order by clause
     *
     * @param string $key
     * @param string $order
     * @return self
     **/
    public function orderBy($key, $order='asc');

    /**
     * Apply an offset
     *
     * @param int $offset
     * @return self
     **/
    public function offset($offset);

    /**
     * Apply a limit
     *
     * @param int $limit
     * @return self
     **/
    public function limit($limit);

    /**
     * For better readability add a pagetype restriction
     * (same as where('page_type_id',$typeOrId))
     *
     * @param Cmsable\PageType\PageType|string pagetype or its id
     * @return self
     **/
    public function pageType($typeOrId);

    /**
     * Normally the pages will be sent through the menu filters to hide invisible
     * pages. Turn it off with this method
     *
     * @param bool $hidden
     * @return self
     **/
    public function withHidden($hidden=true);

    /**
     * Return the results
     *
     * @return \Traversable
     **/
    public function get();

}
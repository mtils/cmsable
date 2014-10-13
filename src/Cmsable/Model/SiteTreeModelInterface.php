<?php namespace Cmsable\Model;

use BeeTree\Ordered\ModelInterface;

interface SiteTreeModelInterface{

    /**
     * @brief Returns a global prefix path for this SiteTree
     *
     * @return string The prefix path (example: admin)
     **/
    public function pathPrefix();

    /**
     * @brief Set a path prefix. This is like a namespace
     *        for this tree. You could have a distinct tree
     *        in /admin or something like that. Or you could put
     *        all cms pages into /cms
     *
     * @param string $prefix The path prefix of this tree
     **/
    public function setPathPrefix($prefix);

    /**
     * @brief Return a page by path. This is used by the routes
     *        to determine which page to show
     *
     * @param string $path The path ($_SERVER['PATH_INFO])
     * @return SiteTreeNodeInterface
     **/
    public function pageByPath($path);

    /**
     * @brief Return a page by its id. The id is normally
     *        the database primary key but dont have to.
     * 
     * @param mixed $id The id of the page to return
     * @return SiteTreeNodeInterface
     **/
    public function pageById($id);

    /**
     * @brief A slightly redundant method for performance reasons
     *        It would be the same as:
     *        SiteTreeModelInterface::pageById(1)->getPath()
     *        But for performance reasons you could fake the
     *        SiteTreeNodeInterface objects until they are needed
     *
     * @see self::pageById
     * @param mixed $id
     * @return SiteTreeNodeInterface
     **/
    public function pathById($id);

    /**
     * @brief Also a performance caused method. Often it is
     *        faster to check before you actually hit the model
     *
     * @param string $path
     * @return bool
     **/
    public function pathExists($path);

    public function pagesByTypeId($pageTypeId);

}
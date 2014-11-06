<?php namespace Cmsable\Model;

use BeeTree\NodeInterface;

interface SiteTreeNodeInterface extends NodeInterface{

    const NONE = 'none';

    const INTERNAL = 'internal';

    const EXTERNAL = 'external';

    const FIRST_CHILD = 'firstchild';

    /**
     * @brief Get the url segment of this node. Cmsable supports
     *        currently only nested segments. So every node has
     *        a segment and node->child->child builds the complete
     *        path (url) to this node (page)
     * @return string The url segment
     **/
    public function getUrlSegment();

    /**
     * @brief Set the url segment
     * @see self::getUrlSegment()
     * @param string $segment
     * @return SiteTreeNodeInterface for fluid syntax
     **/
    public function setUrlSegment($segment);

    /**
     * @brief Return the wohle path (url) to this node
     *        (urlSegment/urlSegment/urlSegment)
     * @return string
     **/
    public function getPath();

    /**
     * @brief Set the path (url) of this node
     * @param $string $path the whole path (url)
     * @return SiteTreeNodeInterface for fluid syntax
     **/
    public function setPath($path);

    /**
     * @brief Returns the id of the page type
     * @return string
     **/
    public function getPageTypeId();

    /**
     * @brief Set the id of its PageType
     * @param string The id
     * @return SiteTreeNodeInterface
     **/
    public function setPageTypeId($id);

    /**
     * @brief Returns the type of redirect this page is
     *
     * @see self::INTERNAL, self::EXTERNAL, self::NONE
     ** @return string
     **/
    public function getRedirectType();

    /**
     * @brief Returns the redirect target. Can be a number for other pages
     *        or a id of another page
     *
     * @see self::getRedirectType()
     ** @return string
     **/
    public function getRedirectTarget();

    public function getMenuTitle();

    public function setMenuTitle($menuTitle);

    public function getTitle();

    public function setTitle($title);

    public function getContent();

    public function setContent($content);
}
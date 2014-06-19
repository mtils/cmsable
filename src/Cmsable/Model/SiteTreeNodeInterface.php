<?php namespace Cmsable\Model;

use BeeTree\NodeInterface;

interface SiteTreeNodeInterface extends NodeInterface{

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
     * @brief Set the id of its pagetype (ControllerDescriptor)
     * @param string The id
     * @return SiteTreeNodeInterface
     **/
    public function setPageTypeId($id);
}
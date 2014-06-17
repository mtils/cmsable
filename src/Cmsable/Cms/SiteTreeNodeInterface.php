<?php namespace Cmsable\Cms;

interface SiteTreeNodeInterface{

    /**
    * @brief Returns if node is a root node
    * 
    * @return void
    */
    public function isRoot();

    /**
    * @brief Returns the parent node of this node
    * 
    * @return SiteTreeNodeInterface
    */
    public function parentNode();

    /**
    * @brief Returns the childs of this node
    * 
    * @return array [SiteTreeNodeInterface]
    */
    public function childNodes();

    public function getUrlSegment();

    public function setUrlSegment($segment);

    public function getPath();

    public function setPath($path);
}
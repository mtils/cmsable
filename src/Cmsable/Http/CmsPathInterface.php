<?php namespace Cmsable\Http;

use Cmsable\Model\SiteTreeNodeInterface;

interface CmsPathInterface{

    public function getPath();

    public function setPath($path);

    public function isCmsPath();

    public function setIsCmsPath($isCmsPath);

    public function getRewrittenPath();

    public function setRewrittenPath($rewrittenPath);

    public function getCmsPathPrefix();

    public function setCmsPathPrefix($prefix);

    public function getNodePath();

    public function setNodePath($nodePath);

    public function getRoutePath();

    public function setRoutePath($routePath);

    public function getMatchedNode();

    public function setMatchedNode(SiteTreeNodeInterface $node);

    public function getFallbackNode();

    public function setFallbackNode(SiteTreeNodeInterface $node);

}
<?php namespace Cmsable\Http;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\PageType\PageType;

class CmsPath{

    public static $homeSegment = 'home';

    protected $originalPath = '';

    protected $isCmsPath = FALSE;

    protected $rewrittenPath = '';

    protected $cmsPathPrefix = '/';

    protected $nodePath = '';

    protected $routePath = '';

    protected $subPath;

    protected $pageType;

    protected $matchedNode;

    protected $fallbackNode;

    public function getOriginalPath(){
        return $this->originalPath;
    }

    public function setOriginalPath($path){
        $this->originalPath = $path;
        return $this;
    }

    public function isCmsPath(){
        return $this->isCmsPath;
    }

    public function setIsCmsPath($isCmsPath){
        $this->isCmsPath = $isCmsPath;
        return $this;
    }

    public function getRewrittenPath(){
        return $this->rewrittenPath;
    }

    public function setRewrittenPath($rewrittenPath){
        $this->rewrittenPath = $rewrittenPath;
        return $this;
    }

    public function getCmsPathPrefix(){
        return $this->cmsPathPrefix;
    }

    public function setCmsPathPrefix($prefix){
        $this->cmsPathPrefix = $prefix;
        return $this;
    }

    public function getNodePath(){
        return $this->nodePath;
    }

    public function setNodePath($nodePath){
        $this->nodePath = $nodePath;
        return $this;
    }

    public function getRoutePath(){
        return $this->routePath;
    }

    public function setRoutePath($routePath){
        $this->routePath = $routePath;
        return $this;
    }

    public function getSubPath(){
        return $this->subPath;
    }

    public function setSubPath($subPath){
        $this->subPath = $subPath;
        return $this;
    }

    public function getPageType(){
        return $this->pageType;
    }

    public function setPageType(PageType $pageType){
        $this->pageType = $pageType;
        return $this;
    }

    public function getMatchedNode(){
        return $this->matchedNode;
    }

    public function setMatchedNode(SiteTreeNodeInterface $node){
        $this->matchedNode = $node;
        return $this;
    }

    public function getFallbackNode(){
        return $this->fallbackNode;
    }

    public function setFallbackNode(SiteTreeNodeInterface $node){
        $this->fallbackNode = $node;
        return $this;
    }

    public function __toString(){
        return $this->getRewrittenPath();
    }

}
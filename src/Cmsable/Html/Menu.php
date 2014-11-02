<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use BeeTree\Helper;

class Menu {

    protected $siteHierarchy = array();

    protected $_loader;

    protected $_root;

    protected $_breadcrumbs = array();

    protected $currentPathProvider;

    protected $currentCmsPath;

    protected $manualCurrentPage;

    protected $routeScope = 'default';

    public function __construct(SiteTreeModelInterface $loader, CurrentCmsPathProviderInterface $currentPathProvider){
        $this->_loader = $loader;
        $this->currentPathProvider = $currentPathProvider;
    }

    public function getRouteScope(){
        return $this->routeScope;
    }

    public function setRouteScope($scope){
        $this->routeScope = $scope;
        return $this;
    }

    public function currentCmsPath(){
        if(!$this->currentCmsPath){
            $this->currentCmsPath = $this->currentPathProvider->getCurrentCmsPath($this->getRouteScope());
        }
        return $this->currentCmsPath;
    }

    public function treeLoader(){
        return $this->_loader;
    }

    public function sub($level, $filter='default'){
        $currentLevel = 1;
        foreach($this->breadcrumbs() as $crumb){
            if($currentLevel == $level){
                return $crumb->filteredChildren($filter);
            }
            $currentLevel++;
        }
        return array();
    }

    public function root(){
        if(!isset($this->_root)){
            $this->_root = $this->_loader->tree();
        }
        return $this->_root;
    }

    public function flat($filter='default'){
        $result = [];
        foreach($this->all($filter) as $node){
            foreach(Helper::flatify($node) as $flatted){
                $result[] = $flatted;
            }
        }
        return $result;
    }

    public function all($filter='default'){
        return $this->root()->filteredChildren($filter);
    }

    public function current(){
        if($this->manualCurrentPage){
            return $this->manualCurrentPage;
        }
        if($node = $this->currentCmsPath()->getMatchedNode()){
            return $this->currentCmsPath()->getMatchedNode();
        }
        return $this->currentCmsPath()->getFallBackNode();
    }

    public function setCurrent($pageOrMenuTitle, $title=NULL, $content=NULL){

        if($pageOrMenuTitle instanceof SiteTreeNodeInterface){
            $page = $pageOrMenuTitle;
        }
        else{

            $page = $this->_loader->makeNode();
            $page->menu_title = $pageOrMenuTitle;
            if(!$title !== NULL){
                $page->title = $title;
            }
            if(!$content !== NULL){
                $page->content = $content;
            }
        }

        if(!$page->id){
            $page->id = -1;
        }

        if($matchedNode = $this->currentCmsPath()->getMatchedNode()){
            $page->setParentNode($matchedNode);
        }

        $this->manualCurrentPage = $page;

        return $this;
    }

    public function inSubPath(){

        $subPath = trim($this->currentCmsPath()->getSubPath(),'/ ');
        if($subPath){
            return TRUE;
        }

        return FALSE;
    }

    public function appendToBreadCrumbs($page){
        $currentPage = $this->current();
        $breadcrumbs = $this->breadcrumbs($currentPage);
        $this->_breadcrumbs[$currentPage->id][] = $page;
    }

    public function breadcrumbs($page=NULL){

        if($page === NULL){
            $page = $this->current();
        }

        if($page){
            $pageId = $page->id;
            if(!isset($this->_breadcrumbs[$pageId])){
                $breadcrumbs = array();
                $breadcrumbs[] = $page;
                while($parent = $page->parentNode()){
                    if(!$parent->isRootNode()){
                        $breadcrumbs[] = $parent;
                    }
                    $page = $parent;
                }
                $this->_breadcrumbs[$pageId] = array_reverse($breadcrumbs);
            }
            return $this->_breadcrumbs[$pageId];
        }
        return array();
    }

    public function isCurrent($page){
        return $this->current()->id == $page->id;
    }

    public function isSection($page){
        foreach($this->breadcrumbs() as $crumb){
            if($crumb->id == $page->id){
                return TRUE;
            }
        }
        return FALSE;
    }

    public function jsTree($currentPageId=NULL, &$string=NULL, $node=NULL){
        if($string === NULL){
            $string = '<ul>';
            $node =  $this->_loader->tree();
        }
        $liClasses = array('jstree-open');
        if($node->isRootNode()){
            $liClasses[] = 'root-node';
        }

        $spanClasses = array();

        if($currentPageId == $node->id){
            $spanClasses[] = 'active';
        }

        $liClass = implode(' ', $liClasses);
        $spanClass = implode(' ', $spanClasses);

        $string .= "\n    <li id=\"sitetree-{$node->id}\" class=\"$liClass\"><span class=\"$spanClass\">{$node->menu_title}</span>";
        if(count($node->childNodes())){
            $string .= "\n    <ul>";
            foreach($node->childNodes() as $child){
                $this->jsTree($currentPageId, $string, $child);
            }
            $string .= "\n    </ul>";
        }
        $string .= "</li>";
        return $string . "\n</ul>";
    }
}
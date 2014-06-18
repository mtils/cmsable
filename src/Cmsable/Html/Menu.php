<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeModelInterface;
use BeeTree\Helper;
use CMS;
class Menu {

    protected $siteHierarchy = array();

    protected $_loader;

    protected $_filter = NULL;

    protected $_root;

    protected $_breadcrumbs = array();

    public function __construct(SiteTreeModelInterface $loader,
                                $filter = NULL){
        $this->_loader = $loader;
        $this->_filter = $filter;
    }

    public function treeLoader(){
        return $this->_loader;
    }

    public function sub($level, $filter=array()){
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

    public function flat(){
        $result = [];
        foreach($this->all() as $node){
            foreach(Helper::flatify($node) as $flatted){
                $result[] = $flatted;
            }
        }
        return $result;
    }

    public function all(){
        return $this->root()->filteredChildren();
    }

    public function current(){
        return CMS::currentPage();
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
            if(!isset($this->_breadcrumbs[$page->id])){
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
        if($currentPageId == $node->id){
            $liClasses[] = 'active';
        }
        $classes = implode(' ', $liClasses);
        $string .= "\n    <li id=\"sitetree-{$node->id}\" class=\"$classes\">{$node->menu_title}";
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
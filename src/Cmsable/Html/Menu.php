<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Routing\ScopeDispatcherTrait;
use Cmsable\Html\Breadcrumbs\Factory as BreadcrumbFactory;
use BeeTree\Helper;

class Menu {

    use ScopeDispatcherTrait;

    protected $_roots = [];

    protected $_breadcrumbs = array();

    protected $crumbFactory;

    protected $currentPathProvider;

    protected $currentCmsPath;

    protected $manualCurrentPage;

    protected $routeScope = 'default';

    public function __construct(CurrentCmsPathProviderInterface $currentPathProvider,
                                BreadcrumbFactory $crumbFactory){

        $this->currentPathProvider = $currentPathProvider;
        $this->crumbFactory = $crumbFactory;

    }

    public function currentCmsPath(){
        if(!$this->currentCmsPath){
            $this->currentCmsPath = $this->currentPathProvider->getCurrentCmsPath($this->getScope());
        }
        return $this->currentCmsPath;
    }

    public function treeLoader(){
        return $this->forwarder();
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
        $scope = $this->getScope(FALSE);
        if(!isset($this->_roots[$scope])){
            $this->_roots[$scope] = $this->treeLoader()->tree();
        }
        return $this->_roots[$scope];
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
        return $this->crumbFactory->get()->last();
    }

    public function setCurrent($pageOrMenuTitle, $title=NULL, $content=NULL){
        return;
        if($pageOrMenuTitle instanceof SiteTreeNodeInterface){
            $page = $pageOrMenuTitle;
        }
        else{

            $page = $this->treeLoader()->makeNode();
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
        return;
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
        foreach($this->crumbFactory->get() as $crumb){
            if($crumb->id == $page->id){
                return TRUE;
            }
        }
        return FALSE;
    }

}
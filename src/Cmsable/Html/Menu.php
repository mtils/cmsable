<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Routing\ScopeDispatcherTrait;
use Cmsable\Html\Breadcrumbs\Factory as BreadcrumbFactory;
use Cmsable\Model\TreeModelManagerInterface;
use Collection\StringList;
use BeeTree\Helper;

class Menu {

    protected $_roots = [];

    protected $_breadcrumbs = array();

    protected $crumbFactory;

    protected $currentPathProvider;

    protected $treeModel;

    protected $treeManager;

    protected $currentCmsPath;

    protected $manualCurrentPage;

    protected $routeScope = 'default';

    protected $cssClassProvider;

    public function __construct(CurrentCmsPathProviderInterface $currentPathProvider,
                                BreadcrumbFactory $crumbFactory,
                                TreeModelManagerInterface $treeManager){

        $this->currentPathProvider = $currentPathProvider;
        $this->crumbFactory = $crumbFactory;
        $this->treeManager = $treeManager;

    }

    public function currentCmsPath(){
        if(!$this->currentCmsPath){
            $this->currentCmsPath = $this->currentPathProvider->getCurrentCmsPath();
        }
        return $this->currentCmsPath;
    }

    public function treeLoader(){
        if(!$this->treeModel){
            $this->treeModel = $this->treeManager->get($this->currentCmsPath()->getTreeScope());
        }
        return $this->treeModel;
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
        if(!isset($this->_roots["main"])){
            $this->_roots["main"] = $this->treeLoader()->tree();
        }
        return $this->_roots["main"];
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

    public function cssClasses($page)
    {

        $cssClasses = new StringList();

        if (!$page instanceof SiteTreeNodeInterface) {
            return $cssClasses;
        }

        $pageTypeClass = basename(str_replace('.','/', $page->getPageTypeId()));
        $cssClasses->append($pageTypeClass);

        if ($provider = $this->cssClassProvider) {
            $provider($page, $cssClasses);
        }

        return $cssClasses;
    }

    public function provideCssClasses(callable $provider)
    {
        $this->cssClassProvider = $provider;
        return $this;
    }

    public function setCurrent($pageOrMenuTitle, $title=NULL, $content=NULL){
        return;
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

    public function isCurrent($page)
    {
        return $this->isSamePage($this->current(), $page);
    }

    public function isSection($page)
    {
        foreach($this->crumbFactory->get() as $crumb){
            if($this->isSamePage($crumb, $page)){
                return true;
            }
        }
        return false;
    }

    protected function isSamePage($pageA, $pageB)
    {
        // If both have no value it should not count as the same
        if (!$pageA->id || !$pageB->id) {
            return false;
        }
        return $pageA->id == $pageB->id;
    }

}

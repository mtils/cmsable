<?php namespace Cmsable\Routing;

use Cmsable\Cms\Action\Action;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Cms\PageType;
use Cmsable\Http\CmsPath;

class SiteTreePathFinder implements SiteTreePathFinderInterface{

    protected $currentPathProvider;

    protected $siteTreeModel;

    public $routeScope = 'default';

    public function __construct(SiteTreeModelInterface $siteTreeModel, CurrentCmsPathProviderInterface $provider){

        $this->currentPathProvider = $provider;
        $this->siteTreeModel = $siteTreeModel;

    }

    public function toPage($pageOrId){

        $page = ($pageOrId instanceof SiteTreeNodeInterface) ? $pageOrId : $this->siteTreeModel->pageById($pageOrId);

        if($page->getRedirectType() == SiteTreeNodeInterface::NONE){
            if($path = $page->getPath()){
                if(ends_with($path, CmsPath::$homeSegment)){
                    return substr($path, 0, strlen($path)-strlen(CmsPath::$homeSegment));
                }
                return $path;
            }
        }

        return $this->recalculatePagePath($page);

    }

    public function toRoutePath($path, $searchMethod=self::NEAREST){
    
    }

    public function toRouteName($name, $searchMethod=self::NEAREST){
    
    }

    public function toPageType($pageType, $searchMethod= self::NEAREST){

        $pageTypeId = ($pageType instanceof PageType) ? $pageType->getId() : $pageType;

        if(!$pages = $this->siteTreeModel->pagesByTypeId($pageTypeId)){
            return '';
        }

        $lowestDepth = 1000;
        $topMost = NULL;
        $i=0;

        foreach($pages as $page){
            if($page->getDepth() < $lowestDepth){
                $topMost = $i;
                $lowestDepth = $page->getDepth();
            }
            $i++;
        }

        return $this->toPage($pages[$topMost]);

    }

    public function toControllerAction($action){

        if(!mb_strpos($action,'@')){
            if($page = $this->currentPathProvider->getCurrentCmsPath($this->routeScope)->getMatchedNode()){
                return $this->toPage($page) . '/' . ltrim($action,'/');
            }
        }

        return '';

    }

    public function toCmsAction(Action $action){
    
    }

    protected function recalculatePagePath(SiteTreeNodeInterface $page){

        if($page->getRedirectType() == SiteTreeNodeInterface::NONE){
            return $this->siteTreeModel->pageById($page->id)->getPath();
        }

        return $this->calculateRedirectPath($page);

    }

    protected function calculateRedirectPath(SiteTreeNodeInterface $redirect, $filter='default'){

        if($redirect->getRedirectType() == SiteTreeNodeInterface::EXTERNAL){

            return $redirect->getRedirectTarget();

        }

        if($redirect->getRedirectType() == SiteTreeNodeInterface::INTERNAL){

            $target = $redirect->getRedirectTarget();

            if(is_numeric($target)){
                if($targetPage = $this->siteTreeModel->pageById((int)$target)){
                    if($targetPage->getRedirectType() == SiteTreeNodeInterface::NONE){
                        return $targetPage->getPath();
                    }
                    else{
                        return '_error_';
                    }
                }
            }
            elseif($target == SiteTreeNodeInterface::FIRST_CHILD){

                if($redirect->hasChildNodes()){
                    if($child = $this->findFirstNonRedirectChild($redirect->filteredChildren($filter), $filter)){
                        return $child->getPath();
                    }
                    else{
                        return '_error_';
                    }
                }

            }
        }
    }

    protected function findFirstNonRedirectChild($childNodes, $filter='default'){

        foreach($childNodes as $child){
            if($child->getRedirectType() == SiteTreeNodeInterface::NONE){
                return $child;
            }
            if($child->hasChildNodes()){
                return $this->findFirstNonRedirectChild($child->filteredChildren($filter));
            }
            break;
        }

    }

}
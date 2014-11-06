<?php namespace Cmsable\Html\Breadcrumbs;

use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Http\CurrentCmsPathProviderInterface;

class SiteTreeCrumbsCreator implements CrumbsCreatorInterface{

    protected $nodeCreator;

    protected $currentPathProvider;

    public function __construct(NodeCreatorInterface $nodeCreator,
                                CurrentCmsPathProviderInterface $pathProvider){

        $this->nodeCreator = $nodeCreator;
        $this->currentPathProvider = $pathProvider;

    }

    public function createCrumbs(){

        if($cmsPath = $this->currentPathProvider->getCurrentCmsPath()){

            $heapNode = NULL;

            if($matchedNode = $cmsPath->getMatchedNode()){
                $heapNode = $matchedNode;
            }
            elseif($fallBackNode = $cmsPath->getFallBackNode()){
                $heapNode = $fallBackNode;
            }

            if($heapNode){
                return $this->buildCrumbsFromHeap($heapNode);
            }

        }
        return new Crumbs($this->nodeCreator);

    }

    protected function buildCrumbsFromHeap($heapNode){

        $reversedCrumbs = [$heapNode];

        while($parent = $heapNode->parentNode()){
            if(!$parent->isRootNode()){
                $reversedCrumbs[] = $parent;
            }
            $heapNode = $parent;
        }


        $crumbs = new Crumbs($this->nodeCreator);

        foreach(array_reverse($reversedCrumbs) as $page){
            $crumbs->append($page);
        }

        return $crumbs;

    }


}
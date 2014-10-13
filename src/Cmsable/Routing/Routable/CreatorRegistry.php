<?php namespace Cmsable\Routing\Routable;

use Illuminate\Http\Request;
use Cmsable\Cms\PageType;
use Cmsable\Model\SiteTreeNodeInterface;

class CreatorRegistry implements CreatorInterface{


    protected $creators = [];

    /**
    * @brief Creates a Routable object which is used to invoke a controller
    * 
    * @param Request $request
    * @param SiteTreeNodeInterface $node
    * @param string $parsedPath
    * @return Routable
    */
    public function createRoutable(Request $request, SiteTreeNodeInterface $node, $parsedPath){
        foreach($this->creators as $creator){
            if($routable = $creator->createRoutable($request, $node, $parsedPath)){
                return $routable;
            }
        }
    }

    public function addCreator(CreatorInterface $creator){
        $this->creators[] = $creator;
        return $this;
    }

    public function removeCreator(CreatorInterface $creator){
        $i = 0;
        $match = -1;
        foreach($this->creators as $myCreator){
            if($myCreator == $creator){
                $match = $i;
                break;
            }
        }
        if($match){
            unset($this->creators[$match]);
            $this->creators = array_values($this->creators);
        }
        return $this;
    }

    public function getCreators(){
        return $this->creators;
    }

}
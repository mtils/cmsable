<?php namespace Cmsable\Routing\Routable;

use Illuminate\Http\Request;
use Illuminate\Routing\ControllerInspector;
use Cmsable\Cms\PageType;
use Cmsable\Cms\PageTypeRepositoryInterface;
use Cmsable\Model\SiteTreeNodeInterface;

abstract class AbstractCreator{

    protected $pageTypes;

    protected $inspector;

    public function __construct(PageTypeRepositoryInterface $pageTypes, ControllerInspector $inspector){

        $this->pageTypes = $pageTypes;
        $this->inspector = $inspector;

    }

    /**
    * @brief Returns the PageType of a node
    *
    *
    * @param SiteTreeNodeInterface $node
    * @return PageType
    */
    protected function getPageType(SiteTreeNodeInterface $node){
        return $this->pageTypes->get($node->getPageTypeId());
    }

    protected function getVerb(Request $request){
        return mb_strtolower($request->getMethod());
    }

    protected function getDefaultMethod(Request $request, $executor){
        $verb = $this->getVerb($request);
        return "{$verb}Index";
    }

    protected function getNonSiteTreePath(SiteTreeNodeInterface $node, $completePath){
        return trim(str_replace($node->getPath(),'',$completePath),'/');
    }

    protected function getFirstSegment($urlPart){
        $parts = explode('/',$urlPart);
        return isset($parts[0]) ? $parts[0] : '';
    }

    public function getNextPart($path){
        $parts = explode('/',trim($path,'/'));
        if(count($parts)){
            array_shift($parts);
            return implode('/',$parts);
        }
        return $path;
    }

    protected function getControllerMethod($controllerClassName, $actionSegment, $verb){
        $routable = $this->inspector->getRoutable($controllerClassName,'');
        $verb = strtolower($verb);
        $controllerMethod = strtolower($verb) . ucfirst(camel_case($actionSegment));
        if($routable){
            foreach ($routable as $method => $routes)
            {
                if($method == $controllerMethod){
                    return $method;
                }
            }
        }
        return '';
    }

    protected function parseParams($urlPart){
        $urlPart = trim($urlPart,'/');
        if(strpos($urlPart,'/')){
            return explode('/', $urlPart);
        }
        elseif(trim($urlPart) != ''){
            return [$urlPart];
        }
        return [];
    }

}
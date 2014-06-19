<?php namespace Cmsable\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Cmsable\Model\SiteTreeModelInterface;

class SiteTreeRoute extends Route{

    protected $parameters = array();

    protected $_treeLoader = NULL;

    protected $_currentPage = NULL;

    protected $descriptorLoader;

    /**
     * Determine if the route matches given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileRoute();
        $myUri = trim($this->uri,'/');
        $requestUri = trim($request->path(),'/');
        $performedHomeFallback = FALSE;

        if($myUri != ''){
            if($requestUri != $myUri){
                $requestTiles = explode('/', $requestUri);
                $myTiles = explode('/', $myUri);
                for($i=0; $i<count($requestTiles); $i++){
                    if(isset($myTiles[$i])){
                        if($requestTiles[$i] != $myTiles[$i]){
                            return FALSE;
                        }
                    }
                }
            }
            if($myUri == $requestUri){
                $requestUri = $myUri.'/home';
                $performedHomeFallback = TRUE;
            }
        }
        else{
            if($requestUri == ''){
                $requestUri = 'home';
                $performedHomeFallback = TRUE;
            }
        }

        $node = $this->treeLoader()->pageByPath($requestUri);

        if($node){

            $this->_currentPage = $node;
            $pageType = $this->descriptorLoader->get($node->getPageTypeId());
            $verb = mb_strtolower($request->getMethod());
            $methodName = "{$verb}Index";

            $this->action['uses'] = function() use($pageType, $methodName, $node){
                $controller = $pageType->createController($node);
                return $controller->$methodName();
            };
            return TRUE;
        }
        // If there is no node found by absolute equality of path choose the
        // last known path Controller and check if action exists
        else{

            $requestSegments = explode('/', $requestUri);

            $pathStack = array();
            $actionSegment = NULL;
            $node = NULL;

            foreach($requestSegments as $segment){
                $pathStack[] = $segment;
                $currentPath = implode('/',$pathStack);
                if(!$this->_treeLoader->pathExists($currentPath)){
                    $actionSegment = $segment;
                    array_pop($pathStack);
                    $parentPath = implode('/',$pathStack);
                    $node = $this->_treeLoader->pageByPath($parentPath);
                    break;
                }
            }
            if($actionSegment && $node){
                $unusedPart = str_replace("$parentPath/$actionSegment",'',
                                          $requestUri);
                $this->_currentPage = $node;

                $pageType = $this->descriptorLoader->get($node->getPageTypeId());

                $controllerMethod = $this->getControllerMethod($pageType->controllerClassName(),
                                                               $actionSegment,
                                                               $request->getMethod());
                if($controllerMethod){
                    $method = $controllerMethod;
                    $this->action['uses'] = function() use($pageType,
                                                        $node,
                                                        $method,
                                                        $unusedPart){
                        $controller = $pageType->createController($node);
                        return $controller->$method(trim($unusedPart,'/'));
                    };
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    
    protected function getControllerMethod($controllerClassName, $actionSegment, $verb){
        $routable = \Route::getInspector()->getRoutable($controllerClassName,'');
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
    }

    public function getDescriptorLoader(){
        return $this->descriptorLoader;
    }

    public function setDescriptorLoader($loader){
        $this->descriptorLoader = $loader;
        return $this;
    }

    public function treeLoader(){
        return $this->_treeLoader;
    }

    public function setTreeLoader(SiteTreeModelInterface $loader){
        $this->_treeLoader = $loader;
        return $this;
    }

    public function currentPage(){
        return $this->_currentPage;
    }

    public function fallbackPage(){
        return $this->_treeLoader->pageByPath('home');
    }
}
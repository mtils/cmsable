<?php namespace Cmsable\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Cms\PageTypeRepositoryInterface;
use Cmsable\Routing\Routable\CreatorInterface;
use App;

class SiteTreeRoute extends Route implements RouteInspectorInterface{

    protected $parameters = array();

    protected $_treeLoader;

    protected $matchedNode;

    protected $matchedRoutable;

    protected $defaultMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

    protected $routableCreator;

    /**
     * Create a new Route instance.
     *
     * @param  array   $methods
     * @param  string  $uri
     * @param  \Closure|array  $action
     * @return void
     */
    public function __construct(SiteTreeModelInterface $treeModel, CreatorInterface $routableCreator, $uri){

        parent::__construct($this->defaultMethods, $uri, function(){});

        $treeModel->setPathPrefix($uri);
        $this->_treeLoader = $treeModel;
        $this->routableCreator = $routableCreator;

    }

    /**
     * Determine if the route matches given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function matches(Request $request, $includingMethod = true)
    {
        $this->compileRoute();

        if(!$this->pathMatchesRoutePrefix($request)){
            return FALSE;
        }

        $requestUri = $this->getTranslatedPath($request);

        // Find matching page
        if(!$node = $this->getFirstMatchingNode($requestUri)){
            return FALSE;
        }

        $this->matchedNode = $node;

        // Find matching Routable
        if(!$routable = $this->routableCreator->createRoutable($request, $node, $requestUri)){
            return FALSE;
        }

        $this->matchedRoutable = $routable;

        $this->action['uses'] =  $routable->getPageType()->createExecutor($routable);

        return TRUE;

    }

    public function pathMatchesRoutePrefix(Request $request){

        $prefix = trim($this->uri,'/');
        $requestUri = trim($request->path(),'/');

        if($prefix != ''){
            if($requestUri != $prefix){
                $requestTiles = explode('/', $requestUri);
                $myTiles = explode('/', $prefix);
                for($i=0; $i<count($requestTiles); $i++){
                    if(isset($myTiles[$i])){
                        if($requestTiles[$i] != $myTiles[$i]){
                            return FALSE;
                        }
                    }
                }
            }
        }
        return TRUE;
    }

    public function getTranslatedPath(Request $request){

        $prefix = trim($this->uri,'/');
        $normalized = trim($request->path(),'/');

        if($prefix != ''){
            if($prefix == $normalized){
                $normalized = $prefix.'/home';
            }
        }
        else{
            if($normalized == ''){
                $normalized = 'home';
            }
        }

        return $normalized;
    }

    public function getFirstMatchingNode($path){

        // If $path matches a node path return it
        if($node = $this->treeLoader()->pageByPath($path)){
            return $node;
        }
        // If not find last matching segment
        else{

            $requestSegments = explode('/', $path);
            $pathStack = array();

            foreach($requestSegments as $segment){

                $pathStack[] = $segment;
                $currentPath = implode('/',$pathStack);

                if(!$this->_treeLoader->pathExists($currentPath)){
                    array_pop($pathStack);
                    $parentPath = implode('/',$pathStack);
                    return $this->_treeLoader->pageByPath($parentPath);
                }
            }
        }

    }

    public function treeLoader(){
        return $this->_treeLoader;
    }

    public function setTreeLoader(SiteTreeModelInterface $loader){
        $this->_treeLoader = $loader;
        return $this;
    }

    public function getMatchedNode(){
        return $this->matchedNode;
    }

    public function getMatchedRoutable(){
        return $this->matchedRoutable;
    }

    public function inSiteTree(){
        return ($this->matchedNode instanceof SiteTreeNodeInterface);
    }

    public function getFallbackPage(){
        return $this->_treeLoader->pageByPath('home');
    }
}
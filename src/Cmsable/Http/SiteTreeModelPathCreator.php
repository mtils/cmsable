<?php namespace Cmsable\Http;

use Symfony\Component\HttpFoundation\Request;
use Cmsable\Http\CmsRequest;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Cms\PageTypeRepositoryInterface;

class SiteTreeModelPathCreator implements CmsPathCreatorInterface{

    protected $siteTreeModel;

    protected $pageTypes;

    protected $cmsPathPrefix = '';

    public function __construct(SiteTreeModelInterface $model,
                                PageTypeRepositoryInterface $pageTypes,
                                $cmsPathPrefix=''){

        $this->siteTreeModel = $model;
        $this->pageTypes = $pageTypes;

        if(!$cmsPathPrefix){
            $cmsPathPrefix = '/';
        }

        if($cmsPathPrefix == '/'){
            $this->cmsPathPrefix = $cmsPathPrefix;
            $model->setPathPrefix($cmsPathPrefix);
        }
        else{
            $this->cmsPathPrefix = trim($cmsPathPrefix,'/');
            $model->setPathPrefix('/' .ltrim($cmsPathPrefix,'/'));
        }
    }

    public function createFromPath($originalPath)
    {

        if(!$this->pathMatchesCmsPathPrefix($originalPath)){
            return $this->createDeactivated($originalPath);
        }

        $cleanedPath = $this->getTranslatedPath($originalPath);


        // Find matching page
        if(!$node = $this->getFirstMatchingNode($cleanedPath)){
            return $this->createDeactivated($originalPath);
        }

        $cmsPath = new CmsPath;

        $cmsPath->setOriginalPath($originalPath);
        $cmsPath->setIsCmsPath(TRUE);
        $cmsPath->setMatchedNode($node);
        $cmsPath->setFallbackNode($this->getFallbackNode());
        $cmsPath->setCmsPathPrefix($this->getCmsPathPrefix());


        $pageType = $this->getPageType($node);
        $cmsPath->setPageType($pageType);

        $targetPath = $pageType->getTargetPath();

        $nodePath = $this->cleanHomeSegment($node->getPath());
        $originalPath = $this->cleanHomeSegment($originalPath);

        $cmsPath->setNodePath($node->getPath());
        $cmsPath->setRoutePath($targetPath);

        $rewrittenPath = trim($this->replacePathHead($nodePath, $targetPath, $originalPath),'/');
        $subPath = trim($this->replacePathHead($nodePath, '', $originalPath),'/');

        if(!$rewrittenPath){
            $rewrittenPath = '/';
        }

        $cmsPath->setRewrittenPath($rewrittenPath);
        $cmsPath->setSubPath($subPath);

        return $cmsPath;

    }

    public function createFromRequest(Request $request)
    {
        if($request instanceof CmsRequest){
            return $this->createFromPath($request->originalPath());
        }
        return $this->createFromPath($request->path());
    }

    public function createDeactivated($originalPath){

        $cmsPath = new CmsPath();
        $cmsPath->setOriginalPath($originalPath);
        $cmsPath->setIsCmsPath(FALSE);
        $cmsPath->setRewrittenPath($originalPath);
        $cmsPath->setCmsPathPrefix('');
        $cmsPath->setNodePath('');
        $cmsPath->setRoutePath($originalPath);
        $cmsPath->setSubPath('');
        $cmsPath->setFallbackNode($this->getFallbackNode());

        return $cmsPath;

    }

    public function getFirstMatchingNode($path){

        // If $path matches a node path return it
        if($node = $this->siteTreeModel->pageByPath($path)){
            return $node;
        }

        // If not find last matching segment
        else{

            $requestSegments = explode('/', $path);
            $pathStack = array();

            foreach($requestSegments as $segment){

                $pathStack[] = $segment;
                $currentPath = implode('/',$pathStack);

                if(!$this->siteTreeModel->pathExists($currentPath)){
                    array_pop($pathStack);
                    $parentPath = implode('/',$pathStack);
                    return $this->siteTreeModel->pageByPath($parentPath);
                }
            }

        }

    }

    public function pathMatchesCmsPathPrefix($originalPath){

        $prefix = $this->getCleanedPathPrefix();
        $requestUri = trim($originalPath,'/');

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

    public function getTranslatedPath($originalPath){

        $prefix = $this->getCleanedPathPrefix();
        $normalized = trim($originalPath,'/');

        if($prefix != ''){
            if($prefix == $normalized){
                $normalized = $prefix.'/'.CmsPath::$homeSegment;
            }
        }
        else{
            if($normalized == ''){
                $normalized = CmsPath::$homeSegment;
            }
        }

        return $normalized;
    }

    protected function getPageType(SiteTreeNodeInterface $node){
        return $this->pageTypes->get($node->getPageTypeId());
    }

    public function getCmsPathPrefix(){
        return $this->cmsPathPrefix;
    }

    public function getCleanedPathPrefix(){
        return trim($this->getCmsPathPrefix(),'/');
    }

    public function getFallbackNode(){

        $pathPrefix = $this->getCleanedPathPrefix();

        if($pathPrefix){
            $path = implode('/', [$pathPrefix,CmsPath::$homeSegment]);
        }
        else{
            $path = CmsPath::$homeSegment;
        }

        return $this->siteTreeModel->pageByPath($path);
    }

    public function replacePathHead($oldHead, $newHead, $path){
        return preg_replace('#'.$oldHead.'#', $newHead, $path, 1);
    }

    public function getSiteTreeModel(){
        return $this->siteTreeModel;
    }

    public function cleanHomeSegment($path){
        if(ends_with($path,CmsPath::$homeSegment)){
            return rtrim(substr($path, 0, strlen($path)-strlen(CmsPath::$homeSegment)),'/');
        }
        return $path;
    }

}
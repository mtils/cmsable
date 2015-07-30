<?php namespace Cmsable\Http;

use DomainException;
use Symfony\Component\HttpFoundation\Request;
use Cmsable\Http\CmsRequest;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\PageType\RepositoryInterface as PageTypeRepository;
use Cmsable\Routing\TreeScope\TreeScope;
use Cmsable\Routing\TreeScope\DetectorInterface;
use Cmsable\Model\TreeModelManagerInterface;

class SiteTreeModelPathCreator implements CmsPathCreatorInterface{

    /**
     * The tree manager, used to choose the right tree model
     *
     * @var \Cmsable\Model\TreeModelManagerInterface
     **/
    protected $treeManager;

    /**
     * The scope detector, used to detect the scope request
     *
     * @var \Cmsable\Routing\TreeScope\DetectorInterface
     **/
    protected $scopeDetector;

    /**
     *
     * @var \Cmsable\PageType\RepositoryInterface
     **/
    protected $pageTypes;

    public function __construct(TreeModelManagerInterface $treeManager,
                                DetectorInterface $scopeDetector,
                                PageTypeRepository $pageTypes){

        $this->treeManager = $treeManager;
        $this->scopeDetector = $scopeDetector;
        $this->pageTypes = $pageTypes;

    }

    public function createFromRequest(Request $request)
    {

        $originalPath = ($request instanceof CmsRequest) ? $request->originalPath() : $request->path();

        $scope = $this->scopeDetector->detectScope($request);

        try {

            // Find matching page
            if(!$node = $this->getFirstMatchingNode($scope, $originalPath)){
                return $this->createDeactivated($scope, $originalPath);
            }

        } catch (DomainException $e) { // if db is empty

            return $this->createDeactivated($scope, $originalPath);

        }

        $cmsPath = new CmsPath;

        $cmsPath->setOriginalPath($originalPath);
        $cmsPath->setIsCmsPath(TRUE);
        $cmsPath->setMatchedNode($node);
        $cmsPath->setFallbackNode($this->getFallbackNode($scope));
        $cmsPath->setCmsPathPrefix($this->cleanPathPrefix($scope->getPathPrefix()));
        $cmsPath->setTreeScope($scope);


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

    public function createDeactivated($scope, $originalPath){

        $cmsPath = new CmsPath();
        $cmsPath->setOriginalPath($originalPath);
        $cmsPath->setIsCmsPath(FALSE);
        $cmsPath->setRewrittenPath($this->removeScope($originalPath, $scope));
        $cmsPath->setCmsPathPrefix('');
        $cmsPath->setTreeScope($scope);
        $cmsPath->setNodePath('');
        $cmsPath->setRoutePath($originalPath);
        $cmsPath->setSubPath('');
        $cmsPath->setFallbackNode($this->getFallbackNode($scope));

        return $cmsPath;

    }

    public function getFirstMatchingNode(TreeScope $scope, $originalPath){

        $pathPrefix = $this->cleanPathPrefix($scope->getPathPrefix());
        $path = $this->getTranslatedPath($originalPath, $pathPrefix);
        $treeModel = $this->treeManager->get($scope);

        // If $path matches a node path return it
        if($node = $treeModel->pageByPath($path)){
            return $node;
        }

        // If not find last matching segment
        else{

            $requestSegments = explode('/', $path);
            $pathStack = array();

            $i = 0;

            foreach($requestSegments as $segment){

                $pathStack[] = $segment;
                $currentPath = implode('/',$pathStack);

                if($this->isPathPrefix($segment, $pathPrefix, $i)){
                    continue;
                }

                $pathExists = $treeModel->pathExists($currentPath);

                if(!$pathExists && $i == 0){
                    return;
                }

                // if the first segment
                if( !$treeModel->pathExists($currentPath)){

                    array_pop($pathStack);
                    $parentPath = implode('/',$pathStack);

                    return $treeModel->pageByPath($parentPath);

                }

                $i++;

            }

        }

    }

    protected function isPathPrefix($segment, $pathPrefix, $segmentIndex){
        return (($segmentIndex == 0) && ($segment == $pathPrefix));
    }

    public function getTranslatedPath($originalPath, $pathPrefix){

        $normalized = trim($originalPath,'/');

        if( ($pathPrefix != '') && ($pathPrefix == $normalized) ){
            return $pathPrefix.'/'.CmsPath::$homeSegment;
        }

        if($normalized == ''){
            return CmsPath::$homeSegment;
        }

        return $normalized;
    }

    protected function getPageType(SiteTreeNodeInterface $node){
        return $this->pageTypes->get($node->getPageTypeId());
    }

    public function cleanPathPrefix($pathPrefix){
        return trim($pathPrefix, '/');
    }

    public function getFallbackNode($scope){

        $pathPrefix = $this->cleanPathPrefix($scope->getPathPrefix());
        $treeModel = $this->treeManager->get($scope);

        if($pathPrefix){
            $path = implode('/', [$pathPrefix,CmsPath::$homeSegment]);
        }
        else{
            $path = CmsPath::$homeSegment;
        }

        try {
            return $treeModel->pageByPath($path);
        } catch (DomainException $e) {
            return $treeModel->makeNode();
        }
    }

    public function removeScope($originalPath, $scope){
        if(trim($scope->getPathPrefix(),'/') == ''){
            return $originalPath;
        }
        return trim($this->replacePathHead($scope->getPathPrefix(), '', $originalPath),'/');
    }

    public function replacePathHead($oldHead, $newHead, $path){
        return preg_replace('#'.$oldHead.'#', $newHead, $path, 1);
    }

    public function cleanHomeSegment($path){
        if(ends_with($path,CmsPath::$homeSegment)){
            return rtrim(substr($path, 0, strlen($path)-strlen(CmsPath::$homeSegment)),'/');
        }
        return $path;
    }

}
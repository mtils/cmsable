<?php namespace Cmsable\PageType;

use BadMethodCallException;

use Cmsable\Support\MultipleProxyTrait;
use Cmsable\Http\CurrentCmsPathProviderInterface;
use Cmsable\Model\SiteTreeModelInterface as TreeModel;
use Cmsable\Model\TreeModelManagerInterface as TreeManager;

class Manager implements CurrentPageTypeProviderInterface{

    use MultipleProxyTrait;

    protected $repository;

    protected $configRepository;

    protected $currentPathProvider;

    protected $treeManager;

    protected $currentPageType = false;

    protected $currentConfig = false;

    protected $pathProvider;

    public function __construct(RepositoryInterface $repo,
                                ConfigRepositoryInterface $configRepo,
                                CurrentCmsPathProviderInterface $pathProvider,
                                TreeManager $treeManager){

        $this->repository = $repo;
        $this->configRepository = $configRepo;
        $this->pathProvider = $pathProvider;
        $this->treeManager = $treeManager;

        $this->addTarget($this->repository);
        $this->addTarget($this->configRepository);

    }

    /**
     * Returns the page config of SiteTreeNodeInterface $page
     * If no page is passed, the config of current page is returned
     *
     * @return \Cmsable\PageType\PageType
     **/
    public function current(){

        if($this->currentPageType !== false){
            return $this->currentPageType;
        }

        $this->currentPageType = null;

        if(!$path = $this->pathProvider->getCurrentCmsPath()){
            return null;
        }

        $this->currentPageType = $path->getPageType();

        return $this->currentPageType;

    }

    /**
     * Returns the pagetype config of PageType $pageType
     * If no page is passed, the config of current page is returned
     *
     * @return ConfigInterface
     **/
    public function currentConfig(){

        if($this->currentConfig !== false){
            return $this->currentConfig;
        }

        $this->currentConfig = null;

        if(!$pageType = $this->current()){
            return;
        }

        $pageId = null;

        if ($page = $this->getBestMatchingPage($pageType)) {
            $pageId = $page->getIdentifier();
        }

        $this->currentConfig = $this->configRepository->getConfig($pageType, $pageId);

        return $this->currentConfig;
    }

    public function getCurrentPage(){

        if(!$path = $this->pathProvider->getCurrentCmsPath()){
            return;
        }

        if(!$page = $path->getMatchedNode()){
            return;
        }

        return $page;

    }

    protected function getBestMatchingPage($pageType) {

        if ($page = $this->getCurrentPage()) {
            return $page;
        }

        return $this->getPageByPageType($pageType);
    }

    protected function getPageByPageType($pageType)
    {

        $scope = $this->pathProvider->getCurrentCmsPath()->getTreeScope();
        $treeModel = $this->treeManager->get($scope);

        if (!$pages = $treeModel->pagesByTypeId($pageType->getId())){
            return;
        }

        if (count($pages) > 1) {
            return;
        }

        return $pages[0];

    }

}
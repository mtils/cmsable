<?php namespace Cmsable\Routing;

use App;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Controller\SiteTree\SiteTreeController;
use Cmsable\Model\AdjacencyListSiteTreeModel;
use Cmsable\Controller\SiteTree\Plugin\Dispatcher;
use Cmsable\Routing\TreeScope\TreeScope;

class SiteTreeControllerCreator implements ControllerCreatorInterface{

    protected $pageTypes;

    /**
     * @brief Creates a controller while routing to a page. This method is used
     *        to configure dependencies of you controller according to a page.
     *        You can also configure your controller directly like setting a
     *        a layout
     *
     * @param string $controllerName The classname of the routed controller
     * @param \Cmsable\Model\SiteTreeNodeInterface $page (optional) Null if no match
     * @return \Illuminate\Routing\Controller
     **/
    public function createController($name, SiteTreeNodeInterface $page=NULL){

        $controller = App::make($name, [App::make('Cmsable\Form\PermissionablePageForm'),$this->getTreeModel(), App::make('events')]);

        $controller->setRouteScope($this->isAdminPageType() ? 'admin' : 'default');

        $this->bootPluginDispatcher();

        return $controller;

    }

    protected function bootPluginDispatcher(){

        $app = App::getFacadeRoot();

        return new Dispatcher($app['Cmsable\PageType\RepositoryInterface'],
                              $app['events'],
                              $app);

    }

    protected function getTreeModel(){

        if($this->isAdminPageType()){
            return $this->getAdminTreeModel();
        }

        if($config = $this->pageTypes()->currentConfig()){
            $scope = $this->getScope($config->sitetree_root_id);
            return $this->getTreeModelManager()->get($scope);
        }

        return $this->getTreeModelManager()->get($this->getScopeRepository()->get(TreeScope::DEFAULT_NAME));

    }

    protected function isAdminPageType(){

        $currentPageType = $this->currentPageType();

        return ($currentPageType && ($currentPageType->getId() == 'cmsable.admin-sitetree-editor'));

    }

    protected function pageTypes(){

        if(!$this->pageTypes){
            $this->pageTypes = App::make('cmsable.pageTypes');
        }

        return $this->pageTypes;

    }

    protected function currentPageType(){
        return $this->pageTypes()->current();
    }

    protected function getScope($modelRootId){
        return $this->getScopeRepository()->getByModelRootId($modelRootId);
    }

    protected function getAdminTreeModel(){
        $scope = $this->getScopeRepository()->get(TreeScope::ADMIN_NAME);
        return $this->getTreeModelManager()->get($scope);
    }

    protected function getTreeModelManager(){
        return App::make('Cmsable\Model\TreeModelManagerInterface');
    }

    protected function getScopeRepository(){
        return App::make('Cmsable\Routing\TreeScope\RepositoryInterface');
    }

}
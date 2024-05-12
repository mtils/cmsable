<?php namespace Cmsable\Routing;

use Cmsable\Controller\SiteTree\Plugin\Dispatcher;
use Cmsable\Controller\SiteTree\SiteTreeController;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Routing\TreeScope\TreeScope;
use Illuminate\Container\Container;
use Illuminate\Routing\Controller;

class SiteTreeControllerCreator implements ControllerCreatorInterface{

    protected $pageTypes;

    /**
     * @brief Creates a controller while routing to a page. This method is used
     *        to configure dependencies of you controller according to a page.
     *        You can also configure your controller directly like setting a
     *        a layout
     *
     * @param string $controllerName The classname of the routed controller
     * @param SiteTreeNodeInterface $page (optional) Null if no match
     * @return Controller
     **/
    public function createController($name, SiteTreeNodeInterface $page=NULL){

        $app = Container::getInstance();

        /** @var SiteTreeController $controller */
        $controller = $app->make($name, [
            'form' => $app->make('Cmsable\Form\PermissionablePageForm'),
            'model' => $this->getTreeModel(),
            'events' => $app->make('events')
        ]);

        $controller->setRouteScope($this->isAdminPageType() ? 'admin' : 'default');

        $this->bootPluginDispatcher();

        return $controller;

    }

    protected function bootPluginDispatcher(){

        $app = Container::getInstance();

        return new Dispatcher($app['Cmsable\PageType\RepositoryInterface'],
                              $app['events'],
                              $app,
                              $app['formobject.factory']);

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
            $this->pageTypes = Container::getInstance()->make('cmsable.pageTypes');
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
        return Container::getInstance()->make('Cmsable\Model\TreeModelManagerInterface');
    }

    protected function getScopeRepository(){
        return Container::getInstance()->make('Cmsable\Routing\TreeScope\RepositoryInterface');
    }

}
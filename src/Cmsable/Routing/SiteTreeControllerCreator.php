<?php namespace Cmsable\Routing;

use App;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Controller\SiteTree\SiteTreeController;
use Cmsable\Model\AdjacencyListSiteTreeModel;
use Cmsable\Controller\SiteTree\Plugin\Dispatcher;

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

        $controller = App::make($name, [App::make('PageForm'),$this->getTreeModel(), App::make('events')]);

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
            return App::make('cmsable.tree-admin');
        }

        if($config = $this->pageTypes()->currentConfig()){
            return new AdjacencyListSiteTreeModel(App::make('cmsable.tree-default')->nodeClassName(),
                                                  $config->sitetree_root_id);
        }

        return App::make('cmsable.tree-default');

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

}
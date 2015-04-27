<?php namespace Cmsable\Controller\SiteTree;

use Illuminate\Events\Dispatcher;
use FormObject\Field\HiddenField;
use FormObject\Support\Laravel\Validator\ValidationException;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use View;
use Input;
use Session;
use URL;
use Redirect;
use RuntimeException;
use CMS;
use PageForm;
use Lang;
use Response;
use Config;
use Illuminate\Routing\Controller;
use BadMethodCallException;

class SiteTreeController extends Controller {

    /**
    * @brief Gibt den Loader für die Page Objekte zurück
    * @var Cmsable\Model\SiteTreeModelInterface
    */
    protected $model;

    /**
    * @brief hier wird bestimmt, welcher Loader benutzt wird
    * @var string
    */
    protected $routeScope = 'default';

    protected $events;

    protected $form;

    protected $mainTemplate = '';

    protected $newPageTemplate = '';

    public function __construct(PageForm $form,
                                SiteTreeModelInterface $model,
                                Dispatcher $events){

        $this->form = $form;
        $this->events = $events;
        $this->form->setRouteScope($this->getRouteScope());
        $this->model = $model;
        $this->registerViewMacros();
    }

    public function index()
    {

        $viewData = array(
            'editedId' => 0,
            'form' => ''
        );

        return View::make($this->getMainTemplate(), $viewData);
    }

    protected function choosePageTypePage(){

        return View::make($this->getNewPageTemplate())
                     ->withParentId(Input::get('parent_id')
        );

    }

    public function create(){

        if(!Input::get('page_type')){
            return $this->choosePageTypePage();
        }

        $parent = $this->getParent();
        $pageTypeId = Input::get('page_type') ? Input::get('page_type') : 'cmsable.page';

        $page = $this->model->makeNode();
        $page->parent_id = $parent->id;
        $page->setPageTypeId($pageTypeId);

        $this->events->fire("sitetree.create", [$page]);

        $pageTypeId = $page->getPageTypeId();

        $this->events->fire("sitetree.$pageTypeId.form-created", [$this->form, $page]);

        $pathPrefix = $this->model->pathById($parent->id);

        $this->form->get('url_segment')->pathPrefix = '/'. ltrim($pathPrefix,'/');

        $this->form->fillByArray($page->toArray());

        $this->events->fire("sitetree.$pageTypeId.form-filled", [$this->form, $page]);

        $this->form->actions->offsetUnset('action_delete');

        $viewData = array(
            'editedPage' => $page,
            'editedId' => 'new',
            'form' => $this->form,
            'message' => ''
        );
        return View::make($this->getMainTemplate(), $viewData);
    }

    public function store(){

        $parent = $this->getParent();
        $pageTypeId = NULL;

        try{

            $page = $this->model->makeNode();

            $this->events->fire("sitetree.store", [$page]);

            $pageTypeId = $page->getPageTypeId();

            $page->fill($this->form->getData());

            $this->events->fire("sitetree.$pageTypeId.creating", [$this->form, $page]);

            $this->model->makeChildOf($page, $parent);

            $this->events->fire("sitetree.$pageTypeId.created", [$this->form, $page]);

            Session::flash('message', $this->getActionMessage('page-created', $page));
            Session::flash('messageType','success');

            return Redirect::action('edit', [$page->id]);

        }
        catch(ValidationException $error){
            return Redirect::to(URL::route('sitetree.create').'?page_type='.$pageTypeId)->withInput()->withErrors($error);
        }
    }

    public function edit($id){

        if(is_numeric($id)){
            $page = $this->model->pageById((int)$id);
            $pageId = $page->id;
            $parentId = $page->parent_id;
        }
        else{
            throw new BadMethodCallException('Wrong id param');
        }

        $this->events->fire("sitetree.edit", [$page]);

        $pageTypeId = $page->getPageTypeId();

        $this->events->fire("sitetree.$pageTypeId.form-created", [$this->form, $page]);

        $pathPrefix = (bool)$parentId ? $this->model->pathById($parentId) . '/' : '/';
        $this->form->get('url_segment')->pathPrefix = '/'. ltrim($pathPrefix,'/');

        $this->form->get('id')->setValue($pageId);
        $this->form->fillByArray($page->toArray());


        $this->events->fire("sitetree.$pageTypeId.form-filled", [$this->form, $page]);

        $viewData = array(
            'editedPage' => $page,
            'editedId' => $pageId,
            'form' => $this->form,
            'message' => ''
        );

        return View::make($this->getMainTemplate(), $viewData);
    }

    public function update($id){

        if(!is_numeric($id)){
            throw new BadMethodCallException('Keine ID übergeben');
        }

        $page = $this->model->makeNode()->findOrFail((int)$id);

        $pageTypeId = $page->getPageTypeId();

        $this->events->fire("sitetree.update", [$page]);

        $this->events->fire("sitetree.$pageTypeId.form-created", [$this->form, $page]);

        $action = $this->form->getSelectedAction()->value;

        if($action == 'delete'){
            return $this->destroy($id);
        }

        if($action != 'submit'){
            throw new NotFoundHttpException();
        }

        try{

            $oldPageTypeId = $page->getPageTypeId();

            $page->fill($this->form->getData(FALSE));

            if($page->getPageTypeId() != $oldPageTypeId){
                $this->events->fire("sitetree.page-type-leaving", [$page, $oldPageTypeId]);
            }

            Session::flash('message',$this->getActionMessage('page-saved',$page));
            Session::flash('messageType','success');

            $this->events->fire("sitetree.$pageTypeId.updating", [$this->form, $page]);

            $this->model->saveNode($page);

            $this->events->fire("sitetree.$pageTypeId.updated", [$this->form, $page]);

            return Redirect::action('edit', [$page->id]);
        }
        catch(ValidationException $error){
            return Redirect::action('edit', [$page->id])->withInput()->withErrors($error);
        }
    }

    public function destroy($id){

        $page = $this->model->makeNode()->findOrFail((int)$id);

        $pageTypeId = $page->getPageTypeId();

        $this->events->fire("sitetree.destroy", [$page]);

        $this->events->fire("sitetree.$pageTypeId.destroying", [$page]);

        $this->model->delete($page);

        $this->events->fire("sitetree.$pageTypeId.destroyed", [$page]);

        Session::flash('message',$this->getActionMessage('page-deleted', $page));
        Session::flash('messageType','success');

        return Redirect::to(URL::currentPage());
    }

    public function move($movedId){

        $parentId = Input::get('into');
        $position = Input::get('position');

        if(!is_numeric($parentId) || !is_numeric($position)){
            throw new RuntimeException('No numeric parentId and position');
        }

        if(!$parentNode = $this->model->pageById($parentId)){
            throw new NotFoundHttpException();
        }

        if(!$movedNode = $this->model->pageById($movedId)){
            throw new NotFoundHttpException();
        }

        $this->events->fire("sitetree.move", [$movedNode, $parentNode]);

        if(!$newAnchestor = $this->findChildByPosition($parentNode, (int)$position)){
            // If there is no anchestor, simply add it as the first child
            if((int)$position == 1){
                $this->model->makeChildOf($movedNode, $parentNode);
                Session::flash('message',$this->getActionMessage('page-moved', $movedNode));
                Session::flash('messageType','success');
            }
            else{
                throw new NotFoundHttpException();
            }
        }
        else{
            $this->model->insertBefore($movedNode, $newAnchestor);
            Session::flash('message',$this->getActionMessage('page-moved', $movedNode));
            Session::flash('messageType','success');
        }

        return Redirect::to(URL::currentPage());
    }

    public function getJsConfig(){

        $content = 'window.cmsEditorCss = "' . Config::get('cmsable.cms-editor-css') . '";';
        $content .= "\nwindow.cmsMessages = ";

        $content .= json_encode(Lang::get('cmsable::messages')) . ';';

        return Response::make($content)->header('Content-Type', 'application/javascript');
    }

    protected function findChildByPosition($parent, $position){

        $posCol = $this->model->sortCol();

        foreach($parent->childNodes() as $child){
            if($child->__get($posCol) == $position){
                return $child;
            }
        }
    }

    protected function getActionMessage($key, $params=array()){

        if($params instanceof SiteTreeNodeInterface){
            $params = $this->getPageMessageParams($params);
        }

        $params = array_merge(
            array('page' => Lang::choice('cmsable::models.page.name',1)),
            $params
        );

        return trans("cmsable::messages.$key", $params);
    }

    protected function getPageMessageParams($page){

        $params = array('title' => '', 'id' => '');

        if($page instanceof SiteTreeNodeInterface){
            $params['title'] = $page->menu_title;
            $params['id'] = $page->getIdentifier();
        }

        return $params;
    }

    /**
    * @brief Gibt den Lader für SiteTree zurück
    * 
    * @return Cmsable\Model\SiteTreeModelInterface
    */
    protected function getModel(){
        return $this->model;
    }

    protected function registerViewMacros(){

        $templates = [
            $this->getNewPageTemplate(),
            $this->getMainTemplate()
        ];

        View::composer($templates, function($view){
            $view->with('routeScope',$this->getRouteScope());
        });

        View::composer($templates, function($view){
            $view->with('sitetreeModel',$this->getModel());
        });

    }

    protected function getMainTemplate(){
        if(!$this->mainTemplate){
            $this->mainTemplate = Config::get('cmsable.sitetree-controller.main-template');
        }
        return $this->mainTemplate;
    }

    protected function getNewPageTemplate(){
        if(!$this->newPageTemplate){
            $this->newPageTemplate = Config::get('cmsable.sitetree-controller.new-page-template');
        }
        return $this->newPageTemplate;
    }

    public function getRouteScope(){
        return $this->routeScope;
    }

    public function setRouteScope($scope){
        $this->routeScope = $scope;
        $this->form->setRouteScope($scope);
        return $this;
    }

    protected function getParent(){
        if(!$parent_id = Input::get('parent_id') ? Input::get('parent_id') : Input::old('parent_id')){
            throw new BadMethodCallException('Missing parent_id');
        }
        if(!$parent = $this->getModel()->pageById($parent_id)){
            throw new NotFoundHttpException();
        }
        return $parent;
    }
}

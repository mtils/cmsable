<?php namespace Cmsable\Controller;

use FormObject\Field\HiddenField;
use FormObject\Support\Laravel\ValidationException;
use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Model\SiteTreeModelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use View;
use AdminMenu;
use Menu;
use Input;
use Session;
use URL;
use Redirect;
use RuntimeException;
use DB; 
use App;
use Event;
use CMS;
use PageForm;
use Lang;
use Response;
use Config;
use HTML;
use Controller;
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
    protected $cmsRouteName = 'default';

    protected $form;

    protected $mainTemplate = '';

    protected $newPageTemplate = '';

    public function __construct(PageForm $form, SiteTreeModelInterface $model){
        $this->form = $form;
        $this->model = $model;
        $this->registerViewMacros();
    }

    public function getIndex()
    {

        $viewData = array(
            'editedId' => 0,
            'form' => ''
        );

        return View::make($this->getMainTemplate(), $viewData);
    }

    public function getNew(){
        $viewData = array(
            'parent_id' => Input::get('parent_id')
        );
        return View::make($this->getNewPageTemplate(), $viewData);
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

    public function getCreate(){

        $parent = $this->getParent();

        $page = $this->model->makeNode();
        $page->parent_id = $parent->id;
        $page->setPageTypeId(Input::get('page_type') ? Input::get('page_type') : 'cmsable.page');

        $pathPrefix = $this->model->pathById($parent->id);

        $this->form->get('url_segment')->pathPrefix = '/'. ltrim($pathPrefix,'/');

        $this->form->fillByArray($page->toArray());

        $this->form->actions->offsetUnset('action_delete');

        $viewData = array(
            'editedPage' => $page,
            'editedId' => 'new',
            'form' => $this->form,
            'message' => ''
        );
        return View::make($this->getMainTemplate(), $viewData);
    }

    public function postCreate(){

        $parent = $this->getParent();

        $page = $this->model->makeNode();

        try{

            $page = $this->model->makeNode();
            $page->fill($this->form->getData());
            $this->model->makeChildOf($page, $parent);

            Session::flash('message', $this->getActionMessage('page-created', $page));
            Session::flash('messageType','success');

            return Redirect::action('edit', [$page->id]);

        }
        catch(ValidationException $error){
            return Redirect::action('create')->withInput()->withErrors($error);
        }
    }

    public function getEdit($id){

        if(is_numeric($id)){
            $page = $this->model->pageById((int)$id);
            $pageId = $page->id;
            $parentId = $page->parent_id;
        }
        else{
            throw new BadMethodCallException('Wrong id param');
        }

        $pageType = CMS::pageTypes()->get($page->getPageTypeId());

        $pageType->getFormPlugin()->modifyForm($this->form);

        $pathPrefix = (bool)$parentId ? $this->model->pathById($parentId) . '/' : '/';

        $pageType->getFormPlugin()->beforeFillForm($this->form, $page);
        $this->form->get('url_segment')->pathPrefix = '/'. ltrim($pathPrefix,'/');

        $this->form->get('id')->setValue($pageId);
        $this->form->fillByArray($page->toArray());
        $pageType->getFormPlugin()->fillForm($this->form, $page);

        $viewData = array(
            'editedPage' => $page,
            'editedId' => $pageId,
            'form' => $this->form,
            'message' => ''
        );

        return View::make($this->getMainTemplate(), $viewData);
    }

    public function postEdit($id){

        if(!is_numeric($id)){
            throw new BadMethodCallException('Keine ID übergeben');
        }

        $page = $this->model->makeNode()->findOrFail((int)$id);

        $pageType = CMS::pageTypes()->get($page->getPageTypeId());

        $pageType->getFormPlugin()->modifyForm($this->form, $page);

        $action = $this->form->getSelectedAction()->value;

        if($action == 'delete'){
            return $this->getDelete($id);
        }

        if($action != 'submit'){
            throw new NotFoundHttpException();
        }

        try{
            $page->fill($this->form->getData(FALSE));

            Session::flash('message',$this->getActionMessage('page-saved',$page));
            Session::flash('messageType','success');

            $pageType->getFormPlugin()->beforeSave($this->form, $page);

            $this->model->saveNode($page);

            $pageType->getFormPlugin()->afterSave($this->form, $page);

            return Redirect::action('edit', [$page->id]);
        }
        catch(ValidationException $error){
            return Redirect::action('edit', [$page->id])->withInput()->withErrors($error);
        }
    }

    public function getDelete($id){

        $page = $this->model->makeNode()->findOrFail((int)$id);

        $this->model->delete($page);

        Session::flash('message',$this->getActionMessage('page-deleted', $page));
        Session::flash('messageType','success');

        return Redirect::to(AdminMenu::current());
    }

    public function getMove($movedId){
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

        return Redirect::to(URL::to(AdminMenu::current()));
    }

    public function getJsConfig(){

        $content = 'window.cmsEditorCss = "' . Config::get('cmsable::cms-editor-css') . '";';
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
        HTML::macro('jsTree', function($editedId){
            return Menu::jsTree($editedId);
        });
    }

    protected function getMainTemplate(){
        if(!$this->mainTemplate){
            $this->mainTemplate = Config::get('cmsable::sitetree-controller.main-template');
        }
        return $this->mainTemplate;
    }

    protected function getNewPageTemplate(){
        if(!$this->newPageTemplate){
            $this->newPageTemplate = Config::get('cmsable::sitetree-controller.new-page-template');
        }
        return $this->newPageTemplate;
    }
}

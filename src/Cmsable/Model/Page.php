<?php namespace Cmsable\Model;

use Eloquent;
use Cmsable\Html\MenuFilter;
use Cmsable\Html\FilteredChildIterator;
use BeeTree\Eloquent\BeeTreeNode;
use Cmsable\Model\SiteTreeNodeInterface;

class Page extends BeeTreeNode implements SiteTreeNodeInterface{

    protected $_path = '';

    public $sortColumn = 'position';

    protected $guarded = array('id','parent_id');

    protected $wholeTreeColumns = array(
        'id',
        'scope_id',
        'controller_name',
        'url_segment',
        'title',
        'menu_title',
        'show_in_menu',
        'show_in_aside_menu',
        'show_in_search',
        'parent_id',
        'position',
        'view_permission',
        'edit_permission',
        'delete_permission',
        'add_child_permission'
    );

    public function filteredChildren(array $filter=array('show_in_menu'=>1)){
        return FilteredChildIterator::create($this->childNodes(),
                                             new MenuFilter($filter));
    }

    public function getUrlSegment(){
        return $this->url_segment;
    }

    public function setUrlSegment($segment){
        $this->url_segment = $segment;
        return $this;
    }

    public function getPath(){
        return $this->path;
    }

    public function setPath($path){
        $this->_path = $path;
        return $this;
    }

    public function getContentAttribute(){
        if(!isset($this->attributes['content']) && $this->exists){
            $this->attributes['content'] = static::where(
                $this->getKeyName(),$this->__get($this->getKeyName())
                )->pluck('content');
        }
        return parent::getAttributeFromArray('content');
    }

    public function getControllerClass(){
        return $this->controller_name;
    }

    public function setControllerClass($className){
        $this->controller_name = $className;
        return $this->controller_name;
    }

    public function canView($user){
        if(!$user){
            if($this->view_permission == 'public-view'){
                return TRUE;
            }
            return FALSE;
        }
        if(strpos('.',$this->view_permission) === FALSE){
            if($user->hasAccess("page.{$this->view_permission}")){
                return TRUE;
            }
        }
        if($user->hasAccess("{$this->view_permission}")){
            return TRUE;
        }
        return $user->isSuperUser();
    }

}
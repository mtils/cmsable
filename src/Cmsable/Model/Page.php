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
        'page_type',
        'url_segment',
        'title',
        'menu_title',
        'show_in_menu',
        'show_in_aside_menu',
        'show_in_search',
        'redirect_type',
        'redirect_target',
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
        return $this->_path;
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

    /**
     * Convert the model's attributes to an array.
     * Reimplemented to retrieve lazy loaded content column
     *
     * @return array
     */
    public function attributesToArray()
    {
        $this->getContentAttribute();
        return parent::attributesToArray();
    }

    public function getPageTypeId(){
        return $this->page_type;
    }

    public function setPageTypeId($id){
        $this->page_type = $id;
        return $this;
    }

    public function canView($user){
        if(!$user){
            if($this->view_permission == 'page.public-view'){
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

    /**
     * @brief Returns the type of redirect this page is
     *
     * @see self::INTERNAL, self::EXTERNAL, self::VIRTUAL, self::NONE
     ** @return string
     **/
    public function getRedirectType(){
        return $this->redirect_type;
    }

    /**
     * @brief Returns the redirect target. Can be a number for other pages
     *        or a id of another page
     *
     * @see self::getRedirectType()
     ** @return string
     **/
    public function getRedirectTarget(){
        return $this->redirect_target;
    }
}
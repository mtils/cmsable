<?php namespace Cmsable\Model;

use Eloquent;
use Cmsable\Html\FilteredChildIterator;
use BeeTree\Eloquent\BeeTreeNode;
use Cmsable\Model\SiteTreeNodeInterface;
use App;

class Page extends BeeTreeNode implements SiteTreeNodeInterface
{

    static protected $visibilityCaster;

    protected $_path = '';

    public $sortColumn = 'position';

    protected $guarded = array('id','parent_id');

    protected $wholeTreeColumns = array(
        'id',
        'root_id',
        'page_type',
        'url_segment',
        'title',
        'menu_title',
        'visibility',
        'redirect_type',
        'redirect_target',
        'parent_id',
        'position',
        'view_permission',
        'edit_permission'
    );

    public function filteredChildren($filter='default'){
        return App::make('Cmsable\Html\MenuFilterRegistry')->filteredChildren($this->childNodes(), $filter);
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

    public function isVisibleIn($menuName)
    {
        $key = "show_in_$menuName";
        if (array_key_exists($key, $this->attributes)) {
            return (bool)$this->attributes[$key];
        }

        if (static::$visibilityCaster) {
            return static::$visibilityCaster->isFlagSelected($key,
                                                             $this->visibility);
        }
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

    public function getMenuTitle(){
        return $this->menu_title;
    }

    public function setMenuTitle($menuTitle){

        $this->menu_title = $menuTitle;
        return $this;

    }

    public function getTitle(){
        return $this->title;
    }

    public function setTitle($title){
        $this->title = $title;
    }

    public function getContent(){
        return $this->content;
    }

    public function setContent($content){

        $this->content = $content;
        return $this;

    }

    public static function getVisibilityCaster()
    {
        return static::$visibilityCaster;
    }

    public static function setVisibilityCaster($caster)
    {
        static::$visibilityCaster = $caster;
    }

}
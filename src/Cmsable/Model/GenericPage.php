<?php namespace Cmsable\Model;

use App;
use BeeTree\NodeInterface;

class GenericPage implements SiteTreeNodeInterface
{

    public $exists = true;

    protected $attributes = [];

    protected $childNodes = [];

    protected $depth;

    protected $parentNode;

    public function __construct(array $attributes = [], $fillChildren=false)
    {
        $this->fill($attributes, $fillChildren);
    }

    public function fill(array $attributes=[], $fillChildren=false)
    {
        $this->attributes = [];

        foreach($attributes as $key=>$value)
        {
            if ($key != 'children') {
                $this->__set($key, $value);
            }
        }

        if (!$fillChildren) {
            return;
        }

        if (!isset($attributes['children']) || !count($attributes['children'])) {
            return;
        }

        $this->clearChildNodes();

        foreach ($attributes['children'] as $childAttributes) {
            $child = new static($childAttributes, true);
            $this->addChildNode($child);
        }

    }

    public function filteredChildren($filter='default'){
        return App::make('Cmsable\Html\MenuFilterRegistry')->filteredChildren($this->childNodes(), $filter);
    }

    public function getUrlSegment(){
        return $this->__get('url_segment');
    }

    public function setUrlSegment($segment){
        $this->__set('url_segment',$segment);
        return $this;
    }

    public function getPath(){
        return $this->__get('path');
    }

    public function setPath($path){
        $this->__set('path', $path);
        return $this;
    }

    public function getPageTypeId(){
        return $this->__get('page_type');
    }

    public function setPageTypeId($id){
        $this->__set('page_type', $id);
        return $this;
    }

    /**
     * @brief Returns the type of redirect this page is
     *
     * @see self::INTERNAL, self::EXTERNAL, self::VIRTUAL, self::NONE
     ** @return string
     **/
    public function getRedirectType(){
        return $this->__get('redirect_type');
    }

    /**
     * @brief Returns the redirect target. Can be a number for other pages
     *        or a id of another page
     *
     * @see self::getRedirectType()
     ** @return string
     **/
    public function getRedirectTarget(){
        return $this->__get('redirect_target');
    }

    /**
     * @return mixed
     */
    public function getRedirectAnchor()
    {
        $this->__get('redirect_anchor');
    }


    /**
    * @brief Returns if node is a root node
    * 
    * @return void
    */
    public function isRootNode(){
        return !(bool)$this->parentNode;
    }

    public function getMenuTitle(){
        return $this->__get('menu_title');
    }

    public function setMenuTitle($menuTitle){
        $this->__set('menu_title', $menuTitle);
        return $this;
    }

    public function getTitle(){
        return $this->__get('title');
    }

    public function setTitle($title){
        $this->__set('title', $title);
    }

    public function getContent(){
        return $this->__get('content');
    }

    public function setContent($content){
        $this->__set('content', $content);
        return $this;
    }

    /**
    * @brief Returns the parent node of this node
    * 
    * @return NodeInterface
    */
    public function parentNode(){
        return $this->parentNode;
    }

    /**
    * @brief Returns the parent node of this node
    * 
    * @return NodeInterface
    */
    public function setParentNode(NodeInterface $parent){
        $this->parentNode = $parent;
        return $this;
    }

    /**
    * @brief Returns the childs of this node
    * 
    * @return array [NodeInterface]
    */
    public function childNodes(){
        return $this->childNodes;
    }

    /**
    * @brief Clears all childNodes
    * 
    * @return array [NodeInterface]
    */
    public function clearChildNodes(){
        $this->childNodes = [];
        return $this;
    }

    /**
    * @brief Adds a childNode to this node
    * 
    * @return NodeInterface
    */
    public function addChildNode(NodeInterface $childNode){
        $this->childNodes[] = $childNode;
        $childNode->setParentNode($this);
        return $this;
    }

    /**
    * @brief Removes a child node
    * 
    * @return NodeInterface
    */
    public function removeChildNode(NodeInterface $childNode){
        $this->childNodes = array_filter($this->childNodes, function($node) use ($childNode){
            return $node !== $childNode;
        });
        return $this;
    }

    /**
    * @brief Does this node have children?
    * 
    * @return bool
    */
    public function hasChildNodes(){
        return (bool)count($this->childNodes);
    }

    /**
    * @brief Returns the depth of this node
    * 
    * @return int
    */
    public function getDepth(){

        if($this->depth === NULL){

            if($this->isRootNode()){
                $this->depth = -1;
            }
            else{
                $parents = [$this];
                $node = $this;
                while($parent = $node->parentNode()){
                    if(!$parent->isRootNode()){
                        $parents[] = $parent;
                    }
                    $node = $parent;
                }
                $this->depth = count($parents);
            }
        }

        return $this->depth;
    }

    /**
    * @brief Set the depth of this node (usually done by BeeTreeModel)
    *
    * @param int $depth
    * @return NodeInterface
    */
    public function setDepth($depth){
        $this->depth = $depth;
        return $this;
    }

    /**
    * @brief Returns the identifier of this node
    *        Identifiers are used to compare nodes and deceide which
    *        child depends to which parent.
    *        In a filesystem the path would be the identifier, in
    *        a database a id column.
    * 
    * @return mixed
    */
    public function getIdentifier(){
        return $this->__get('id');
    }

    /**
    * @brief Returns the identifier of the parent
    * 
    * @return mixed
    */
    public function getParentIdentifier(){
        if ($this->parentNode) {
            return $this->parentNode->getIdentifier();
        }
    }

    /**
    * @brief Returns the identifier of the parent
    * 
    * @return mixed
    */
    public function hasParentNode(){
        return (bool)$this->parentNode;
    }

    public function __get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

}
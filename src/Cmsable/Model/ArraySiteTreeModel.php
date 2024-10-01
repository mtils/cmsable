<?php namespace Cmsable\Model;

use BadMethodCallException;
use Cmsable\Html\Breadcrumbs\NodeCreatorInterface;
use Ems\Core\Patterns\HookableTrait;

class ArraySiteTreeModel implements SiteTreeModelInterface, NodeCreatorInterface
{

    use HookableTrait;

    protected $_pathPrefix = '';

    protected $pathMap;

    protected $id2Path;

    protected $pagesByTypeId = [];

    protected $pageTypeToPages = [];

    protected $rootNode;

    protected $rootId;

    protected $nodeClassName;

    protected $sourceArray;

    protected $arrayProvider;

    protected $_idLookup;

    public function __construct($pageClassName='\Cmsable\Model\GenericPage',
                                $rootId=1){

        $this->setRootId($rootId);
        $this->nodeClassName = $pageClassName;

    }

    public function getRootId(){
        return $this->rootId;
    }

    public function setRootId($rootId){
        $this->rootId = $rootId;
        return $this;
    }

    public function getSourceArray()
    {
        if ($this->sourceArray === null && $provider = $this->arrayProvider) {
            $this->sourceArray = [];
            $this->setSourceArray($provider($this));
        }
        return $this->sourceArray;
    }

    public function setSourceArray(array $array)
    {
        foreach ($this->getListeners('setSourceArray', 'before') as $listener) {
            $listener($this->sourceArray);
        }

        $this->sourceArray = $array;

        foreach ($this->getListeners('setSourceArray', 'after') as $listener) {
            $listener($this->sourceArray);
        }

        return $this;
    }

    /**
     * @brief Returns a global prefix path for this SiteTree
     *
     * @return string The prefix path (example: admin)
     **/
    public function pathPrefix(){
        return $this->_pathPrefix;
    }

    /**
     * @brief Set a path prefix. This is like a namespace
     *        for this tree. You could have a distinct tree
     *        in /admin or something like that. Or you could put
     *        all cms pages into /cms
     *
     * @param string $prefix The path prefix of this tree
     **/
    public function setPathPrefix($prefix){
        $this->_pathPrefix = $prefix;
        return $this;
    }

    /**
     * @brief Return a page by path. This is used by the routes
     *        to determine which page to show
     *
     * @param string $path The path ($_SERVER['PATH_INFO])
     * @return SiteTreeNodeInterface
     **/
    public function pageByPath($path){

        $this->ensureLookups();

        if(isset($this->pathMap[$path])){
            return $this->pathMap[$path];
        }
    }

    /**
     * @brief Return a page by its id. The id is normally
     *        the database primary key but dont have to.
     *
     * @param mixed $id The id of the page to return
     * @return SiteTreeNodeInterface
     **/
    public function pageById($id){
        return $this->get($id);
    }

    /**
     * @brief A slightly redundant method for performance reasons
     *        It would be the same as:
     *        SiteTreeModelInterface::pageById(1)->getPath()
     *        But for performance reasons you could fake the
     *        SiteTreeNodeInterface objects until they are needed
     *
     * @see self::pageById
     * @param mixed $id
     * @return SiteTreeNodeInterface
     **/
    public function pathById($id){
        $this->ensureLookups();
        if(isset($this->id2Path[$id])){
            return $this->id2Path[$id];
        }
    }

    /**
     * @brief Also a performance caused method. Often it is
     *        faster to check before you actually hit the model
     *
     * @param string $path
     * @return bool
     **/
    public function pathExists($path){
        $this->ensureLookups();
        return isset($this->pathMap[$path]);
    }

    public function pagesByTypeId($pageTypeId){
        $this->ensureLookups();
        if(isset($this->pagesByTypeId[$pageTypeId])){
            return $this->pagesByTypeId[$pageTypeId];
        }
        return [];
    }

    /**
     * @brief Retrieve a tree by its _ID_. Reimplemented to ensure only pages of this tree are loaded
     *
     * @param mixed $identifier The id of this node, which is the same as node->getIdentifier()
     * @param mixed $rootId The rootId of the tree, optional to speed up the initial query
     * @return NodeInterface
     **/
    public function get($identifier, $rootId=NULL){
        if(!isset($this->_idLookup[$identifier])){
            $this->tree();
        }
        if(isset($this->_idLookup[$identifier])){
            return $this->_idLookup[$identifier];
        }
    }

    public function tree($rootId = NULL){

        if($rootId === NULL || $rootId == $this->getRootId()){
            $rootId = $this->getRootId();
        }
        else{
            throw new BadMethodCallException('This SiteTreeModel can only retrieve nodes of '.$this->rootCol().'='.$rootId);
        }

        $this->ensureLookups();

        return $this->rootNode;
    }

    protected function ensureLookups(){
        if($this->pathMap === NULL){
            $this->buildLookups();
        }
    }

    protected function buildLookups($childs=NULL, $currentStack=NULL){

        if( $childs=== NULL && $currentStack === NULL ){
            $currentStack = $this->getEmptyPathStack();
            $this->rootNode = $this->newNode();
            $this->rootNode->fill($this->getSourceArray(), true);
            $childs = $this->rootNode->childNodes();
            $this->pathMap = [];
            $this->id2Path = [];
            $this->pageTypeToPages = [];
            $this->id2Path[$this->rootNode->id] = $this->rootNode->getUrlSegment();
            $this->_idLookup[$this->rootNode->id] = $this->rootNode;
        }

        foreach($childs as $child){
            $urlSegment = $child->getUrlSegment();
            $currentStack[] = $urlSegment;

            if($child->hasChildNodes()){
                $this->buildLookups($child->childNodes(), $currentStack);
            }

            $path = implode('/',$currentStack);

            $child->setPath(trim($path,'/'));

            $this->pathMap[$path]  = $child;
            $this->id2Path[$child->id] = $path;
            $this->_idLookup[$child->id] = $child;

            if(!isset($this->pagesByTypeId[$child->getPageTypeId()])){
                $this->pagesByTypeId[$child->getPageTypeId()] = [];
            }

            $this->pagesByTypeId[$child->getPageTypeId()][] = $child;

            array_pop($currentStack);

            if($urlSegment == 'home'){
                $homePath = implode("/",$currentStack);
                if($homePath == ''){
                    $homePath = '/';
                }
                $this->pathMap[$homePath]  = $child;
                $this->id2Path[$child->id] = $homePath;
            }
        }

    }

    private function getEmptyPathStack(){
        $myUri = trim($this->_pathPrefix, '/');
        if($myUri == ''){
            return array();
        }
        else{
            return explode('/', $myUri);
        }
    }

    public function makeNode(){
        $nodeClass = $this->nodeClassName;
        return new $nodeClass;
    }

    public function newNode(){
        return static::makeNode();
    }

    public function provideArray(callable $provider)
    {
        $this->arrayProvider = $provider;
        return $this;
    }

}

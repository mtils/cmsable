<?php namespace Cmsable\Model;

use BeeTree\Eloquent\OrderedAdjacencyListModel;
use BadMethodCallException;

class AdjacencyListSiteTreeModel extends OrderedAdjacencyListModel implements SiteTreeModelInterface{

    protected $_pathPrefix = '';

    protected $pathMap;

    protected $id2Path;

    protected $rootId;

    public function __construct($pageClassName='\Cmsable\Model\Page', $rootId=1){

        $this->setRootId($rootId);
        $this->setNodeClassName($pageClassName);

    }

    public function getRootId(){
        return $this->rootId;
    }

    public function setRootId($rootId){
        $this->rootId = $rootId;
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
            throw new BadMethodCallException('This SiteTreeModel can only retrieve nodes of '.$this->rootCol().'='.$this->rootId());
        }

        $this->ensureLookups();

        return parent::tree($rootId);
    }

    protected function ensureLookups(){
        if($this->pathMap === NULL){
            $this->buildLookups();
        }
    }

    protected function buildLookups($childs=NULL, $currentStack=NULL){

        if( $childs=== NULL && $currentStack === NULL ){
            $currentStack = $this->getEmptyPathStack();
            $root = parent::tree($this->getRootId());
            $childs = $root->childNodes();
            $this->pathMap = array();
            $this->id2Path = array();
            $this->id2Path[$root->id] = $root->getUrlSegment();
        }

        $redirects = array();

        foreach($childs as $child){
            $urlSegment = $child->getUrlSegment();
            $currentStack[] = $urlSegment;

            if($child->childNodes()){
                $this->buildLookups($child->childNodes(), $currentStack);
            }

            $path = implode('/',$currentStack);

            if($child->getRedirectType() == SiteTreeNodeInterface::NONE){
                $child->setPath(trim($path,'/'));
            }
            else{
                $redirects[] = $child;
            }

            $this->pathMap[$path]  = $child;
            $this->id2Path[$child->id] = $path;

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

        $this->processRedirects($redirects);

    }

    protected function processRedirects($redirects){

        foreach($redirects as $redirect){

            if($redirect->getRedirectType() == SiteTreeNodeInterface::EXTERNAL){

                $redirect->setPath($redirect->getRedirectTarget());

            }
            elseif($redirect->getRedirectType() == SiteTreeNodeInterface::INTERNAL){

                $target = $redirect->getRedirectTarget();

                if(is_numeric($target)){
                    if($targetPage = $this->pageById((int)$target)){
                        if($targetPage->getRedirectType() == SiteTreeNodeInterface::NONE){
                            $redirect->setPath($targetPage->getPath());
                        }
                        else{
                            $redirect->setPath('_error_');
                        }
                    }
                }
                elseif($target == SiteTreeNodeInterface::FIRST_CHILD){
                    if($redirect->hasChildNodes()){
                        if($child = $this->findFirstNonRedirectChild($redirect->childNodes())){
                            $redirect->setPath($child->getPath());
                        }
                        else{
                            $redirect->setPath('_error_');
                        }
                    }
                }
            }
        }

    }

    protected function findFirstNonRedirectChild($childNodes){
        foreach($childNodes as $child){
            if($child->getRedirectType() == SiteTreeNodeInterface::NONE){
                return $child;
            }
            if($child->hasChildNodes()){
                return $this->findFirstNonRedirectChild($child->childNodes());
            }
            break;
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
        $node = parent::makeNode();
        $node->__set($this->rootCol(), $this->getRootId());
        return $node;
    }

}

<?php namespace Cmsable\Routing\TreeScope;

use OutOfBoundsException;

use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Model\SiteTreeNodeInterface;

class RootNodeRepository implements RepositoryInterface{

    /**
     * All scopes
     *
     * @var array
     **/
    protected $scopes;

    /**
     * Scopes by name
     *
     * @var array
     **/
    protected $scopeByName = [];

    /**
     * Scopes by path-prefix
     *
     * @var array
     **/
    protected $scopeByPathPrefix = [];

    /**
     * Scopes by root id
     *
     * @var array
     **/
    protected $scopeByModelRootId = [];

    /**
     * A reference to the main sitetree model
     *
     * @var BeeTree\ModelInterface
     **/
    protected $treeModel;

    public function __construct(SiteTreeModelInterface $treeModel){

        $this->treeModel = $treeModel;

    }

    /**
     * Returns all available scopes
     *
     * @return \Traversable
     **/
    public function getAll(){

        $this->fillScopes();

        return $this->scopes;

    }

    /**
     * Returns the scope with name $name
     *
     * @throws OutOfBoundsException If no scope with name $name was found
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function get($name){

        $this->fillScopes();

        if(!isset($this->scopeByName[$name])){
            throw new OutOfBoundsException("No scope named '$name' found");
        }

        return $this->scopeByName[$name];

    }

    /**
     * Returns the scope by $pathPrefix
     *
     * @throws OutOfBoundsException If no scope with pathprefix $pathPrefix was found
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function getByPathPrefix($pathPrefix){

        $this->fillScopes();

        if(!isset($this->scopeByPathPrefix[$pathPrefix])){
            throw new OutOfBoundsException("No scope with pathprefix '$pathPrefix' found");
        }

        return $this->scopeByPathPrefix[$pathPrefix];

    }

    /**
     * Returns the scope by modelRootId
     *
     * @throws OutOfBoundsException If no scope with root-id $rootId was found
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function getByModelRootId($rootId){

        $this->fillScopes();

        if(!isset($this->scopeByModelRootId[$rootId])){
            throw new OutOfBoundsException("No scope with modelRootId '$rootId' found");
        }

        return $this->scopeByModelRootId[$rootId];

    }

    /**
     * Fills the scope array for fast lookups
     *
     * @return void
     **/
    protected function fillScopes(){

        if($this->scopes !== null){
            return;
        }

        $this->scopes = [];

        foreach($this->treeModel->rootNodes() as $rootNode){

            $scope = $this->node2Scope($rootNode);

            $this->scopes[] = $scope;
            $this->scopeByName[$scope->getName()] = $scope;
            $this->scopeByModelRootId[$scope->getModelRootId()] = $scope;
            $this->scopeByPathPrefix[$scope->getPathPrefix()] = $scope;

        }

    }

    /**
     * Convert a root node to a scope
     *
     * @param \Cmsable\Model\SiteTreeNodeInterface $node
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function node2Scope(SiteTreeNodeInterface $node){

        $scope = new TreeScope();
        $urlSegment = trim($node->getUrlSegment(),'/');

        $name = $urlSegment ? $urlSegment : TreeScope::DEFAULT_NAME;

        $scope->setPathPrefix($node->getUrlSegment());
        $scope->setName($name);
        $scope->setTitle($node->getMenuTitle());
        $scope->setModelRootId($node->{$this->treeModel->rootCol()});

        return $scope;

    }

}
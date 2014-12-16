<?php namespace Cmsable\Model;

use Cmsable\Routing\TreeScope\TreeScope;

class TreeModelManager implements TreeModelManagerInterface{

    /**
     * Here the models are held by name and
     *
     * @var array
     **/
    protected $models = [];

    /**
     * A prototype of SiteTreeModelInterface to clone
     *
     * @var \Cmsable\Model\SiteTreeModelInterface
     **/
    protected $treeModelPrototype;


    public function __construct(SiteTreeModelInterface $treeModel){
        $this->treeModelPrototype = $treeModel;
    }

    /**
     * Returns a SiteTreeModel for scope $scope
     *
     * @param \Cmsable\Routing\TreeScope\TreeScope $scope
     * @return \Cmsable\Model\SiteTreeModelInterface
     **/
    public function get(TreeScope $scope){

        $scopeId = $this->getScopeId($scope);

        if(!isset($this->models[$scopeId])){
            $this->models[$scopeId] = $this->makeModel($scope);
        }

        return $this->models[$scopeId];

    }

    /**
     * Set a SiteTreeModel for scope $scope
     *
     * @param \Cmsable\Routing\TreeScope\TreeScope $scope
     * @param \Cmsable\Model\SiteTreeModelInterface $model
     * @return self
     **/
    public function set(TreeScope $scope, SiteTreeModelInterface $model){

        $scopeId = $this->getScopeId($scope);
        $this->models[$scopeId] = $model;

        return $this;

    }

    /**
     * Return a unique id for a scope. Mostly used as a cache-id in its local
     * models array
     *
     * @param \Cmsable\Routing\TreeScope\TreeScope $scope
     * @return string
     **/
    protected function getScopeId(TreeScope $scope){
        return $scope->getName() . '|' . $scope->getModelRootId();
    }

    /**
     * Make a sitetree model
     *
     * @param \Cmsable\Routing\TreeScope\TreeScope $scope
     * @return \Cmsable\Model\SiteTreeModelInterface
     **/
    protected function makeModel(TreeScope $scope){

        $model = clone $this->treeModelPrototype;
        $model->setRootId($scope->getModelRootId());
        $model->setPathPrefix($scope->getPathPrefix());

        return $model;

    }

}
<?php namespace Cmsable\Model;

use Cmsable\Routing\TreeScope\TreeScope;

interface TreeModelManagerInterface{

    /**
     * Returns a SiteTreeModel for scope $scope
     *
     * @param \Cmsable\Routing\TreeScope\TreeScope $scope
     * @return \Cmsable\Model\SiteTreeModelInterface
     **/
    public function get(TreeScope $scope);

    /**
     * Set a SiteTreeModel for scope $scope
     *
     * @param \Cmsable\Routing\TreeScope\TreeScope $scope
     * @param \Cmsable\Model\SiteTreeModelInterface $model
     * @return \Cmsable\Model\SiteTreeModelInterface
     **/
    public function set(TreeScope $scope, SiteTreeModelInterface $model);

}
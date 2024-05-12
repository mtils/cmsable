<?php namespace Cmsable\Model;

use Cmsable\Routing\TreeScope\TreeScope;

interface TreeModelManagerInterface{

    /**
     * Returns a SiteTreeModel for scope $scope
     *
     * @param TreeScope $scope
     * @return SiteTreeModelInterface
     **/
    public function get(TreeScope $scope);

    /**
     * Set a SiteTreeModel for scope $scope
     *
     * @param TreeScope $scope
     * @param SiteTreeModelInterface $model
     * @return SiteTreeModelInterface
     **/
    public function set(TreeScope $scope, SiteTreeModelInterface $model);

}
<?php namespace Cmsable\Routing\TreeScope;

interface CurrentTreeScopeProviderInterface{

    public function getCurrentScope();

    public function setCurrentScope(TreeScope $scope);

}
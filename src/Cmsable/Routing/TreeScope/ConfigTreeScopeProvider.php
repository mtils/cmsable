<?php namespace Cmsable\Routing\TreeScope;

use Config;

class ConfigTreeScopeProvider{

    public $configKey = 'cmsable::default_scope_id';

    protected $currentScope;

    public function getCurrentScope(){

        if(!$this->currentScope){
            $scope = new TreeScope();
            $scope->setModelRootId(Config::get($this->configKey));
            return $scope;
        }

        return $this->currentScope;
    }

    public function setCurrentScope(TreeScope $scope){
        $this->currentScope = $scope;
        return $this;
    }

}
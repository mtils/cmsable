<?php namespace Cmsable\Routing;

trait ScopeDispatcherTrait{

    public $defaultScope = 'default';

    protected $forwards = [];

    protected $temporalScope;

    protected $currentScopeProvider;

    public function scope($scope){
        $this->temporalScope = $scope;
        return $this;
    }

    public function resetScope(){
        $this->temporalScope = NULL;
        return $this;
    }

    public function getScope($deleteAfter=FALSE){

        if($this->temporalScope){
            $scope = $this->temporalScope;
            if($deleteAfter){
                $this->temporalScope = NULL;
            }
        }
        else{
            if(!$scope = $this->getCurrentScopeProvider()->currentScope()){
                return $this->defaultScope;
            }
        }

        return $scope;
    }

    public function forwarder($scope = NULL){
        $scope = ($scope === NULL) ? $this->getScope() : $scope;
        return $this->forwards["$scope"];
    }

    public function setForwarder($scope, $forwarder){
        $this->forwards["$scope"] = $forwarder;
        return $this;
    }

    public function forwarders(){
        return array_values($this->forwards);
    }

    public function getCurrentScopeProvider(){
        return $this->currentScopeProvider;
    }

    public function setCurrentScopeProvider(CurrentScopeProviderInterface $provider){
        $this->currentScopeProvider = $provider;
        return $this;

    }

}
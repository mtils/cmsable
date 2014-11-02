<?php namespace Cmsable\Routing;

use Illuminate\Routing\UrlGenerator;

use Cmsable\Html\SiteTreeUrlGenerator;

class SiteTreeUrlDispatcher extends UrlGenerator{

    protected $generators = [];

    protected $currentScopeProvider;

    protected $temporalScope;

    public function scope($scope){
        $this->temporalScope = $scope;
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
                return 'default';
            }
        }

        return $scope;
    }

    public function addUrlGenerator($routeScope, SiteTreeUrlGenerator $generator){
        $this->generators[$routeScope] = $generator;
    }

    public function generator(){
        return $this->generators[$this->getScope()];
    }

    public function getCurrentScopeProvider(){
        return $this->currentScopeProvider;
    }

    public function setCurrentScopeProvider($provider){
        $this->currentScopeProvider = $provider;
        return $this;

    }

    /**
     * Generate a absolute URL to the given path.
     *
     * @param  mixed  $path or SiteTreeNodeInterface Instance
     * @param  mixed  $extra
     * @param  bool  $secure
     * @return string
     */
    public function to($path, $extra = array(), $secure = null){
        return $this->generator()->to($path, $extra, $secure);
    }

    /**
    * Get the URL to a controller action.
    *
    * @param  string  $action
    * @param  mixed   $parameters
    * @param  bool    $absolute
    * @return string
    */
    public function action($action, $parameters = array(), $absolute = true)
    {
        return $this->generator()->action($action, $parameters, $absolute);
    }

    public function page($page=NULL, $extra = array(), $secure = null){

        return $this->generator()->page($page, $extra, $secure);

    }

    public function currentPage($extra=[], $secure = null){

        return $this->generator()->currentPage($extra, $secure);

    }

}
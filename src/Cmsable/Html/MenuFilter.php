<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Auth\CurrentUserProviderInterface;
use Cmsable\Auth\PermissionableInterface;

class MenuFilter{

    protected $filters = array();

    protected $userProvider;

    public function isVisible(SiteTreeNodeInterface $page){
        if(!$this->checkUserPermission($page)){
            return FALSE;
        }
        foreach($this->filters as $property=>$value){
            if($page->__get($property) != $value){
                return FALSE;
            }
        }
        return TRUE;
    }

    protected function checkUserPermission(SiteTreeNodeInterface $page){
        if($this->userProvider and $page instanceof PermissionableInterface){
            return $page->isAllowed('view', $this->userProvider->current());
        }
        return TRUE;
    }

    public function getFilters(){
        return $this->filters;
    }

    public function setFilters(array $filters){
        $this->filters = $filters;
        return $this;
    }

    public function setFilter($key, $value){
        $this->filters[$key] = $value;
        return $this;
    }

    public static function create(){
        $class = get_called_class();
        return new $class();
    }

    public function getUserProvider(){
        return $this->userProvider;
    }

    public function setUserProvider(CurrentUserProviderInterface $provider){
        $this->userProvider = $provider;
        return $this;
    }
}
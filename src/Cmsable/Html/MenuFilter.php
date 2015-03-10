<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;
use Cmsable\Auth\CurrentUserProviderInterface;
use DomainException;

class MenuFilter{

    protected $filters = array();

    public function isVisible(SiteTreeNodeInterface $page){
        foreach($this->filters as $filter){
            if(!$filter($page)){
                return FALSE;
            }
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

    public function add($name, $filter){

        if(!is_callable($filter)){
            throw new DomainException("Filter has to be callable");
        }

        $this->filters[$name] = $filter;

        return $this;
    }

    public static function create(){
        $class = get_called_class();
        return new $class();
    }

}
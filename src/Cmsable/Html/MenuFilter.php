<?php namespace Cmsable\Html;

use Cmsable\Model\SiteTreeNodeInterface;

class MenuFilter implements MenuFilterInterface{

    protected $filter;

    public function __construct(array $filter=array()){
        $this->filter = $filter;
    }

    public function isVisible(SiteTreeNodeInterface $page){
        foreach($this->filter as $property=>$value){
            if($page->__get($property) != $value){
                return FALSE;
            }
        }
        return TRUE;
    }
}
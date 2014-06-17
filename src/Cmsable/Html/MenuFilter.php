<?php namespace Cmsable\Html;

use Sentry;
use Cmsable\Model\SiteTreeNodeInterface;

class MenuFilter{

    protected $filter;

    public function __construct(array $filter=array()){
        $this->filter = $filter;
    }

    public function isVisible(SiteTreeNodeInterface $page){
        if(!$page->canView(Sentry::getUser())){
            return FALSE;
        }
        foreach($this->filter as $property=>$value){
            if($page->__get($property) != $value){
                return FALSE;
            }
        }
        return TRUE;
    }
}
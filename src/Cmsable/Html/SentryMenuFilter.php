<?php namespace Cmsable\Html;

use Sentry;
use Cmsable\Model\SiteTreeNodeInterface;

class SentryMenuFilter extends MenuFilter{

    public function isVisible(SiteTreeNodeInterface $page){
        if(!$page->canView(Sentry::getUser())){
            return FALSE;
        }
        return parent::isVisible($page);
    }
}
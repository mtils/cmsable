<?php namespace Cmsable\Controller;

use CMS;
use Redirect;
use Controller;
use Cmsable\Model\SiteTreeNodeInterface;
use App;

class RedirectorController extends Controller{

    public function getIndex()
    {
        if($page = CMS::getMatchedNode()){
            if($page->getRedirectType() != SiteTreeNodeInterface::NONE){
                $target = $page->getPath();
                if($target != '_error_'){
                    return Redirect::to($target);
                }
            }
        }
        App::abort(500);
    }

}

<?php namespace Cmsable\Controller;

use CMS;
use Redirect;
use Illuminate\Routing\Controller;
use Cmsable\Model\SiteTreeNodeInterface;
use App;
use URL;

class RedirectorController extends Controller{

    public function index()
    {
        if($page = CMS::getMatchedNode()){
            if($page->getRedirectType() != SiteTreeNodeInterface::NONE){
                $target = $page->getPath();
                if($target != '_error_'){
                    return Redirect::to(URL::to($page));
                }
            }
        }
        App::abort(500);
    }

}

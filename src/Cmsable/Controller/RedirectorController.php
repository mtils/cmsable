<?php namespace Cmsable\Controller;

use RuntimeException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Cmsable\Model\SiteTreeNodeInterface as SiteTreeNode;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Cmsable\Http\CmsRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Contracts\Routing\UrlGenerator;
use URL;

class RedirectorController extends Controller
{

    protected $redirector;

    protected $url;

    public function __construct(Redirector $redirector, UrlGenerator $url)
    {
        $this->redirector = $redirector;
        $this->url = $url;
    }

    public function index(Request $request)
    {

        $page = $this->getPageFromRequest($request);

        if ($page->getPageTypeId() == 'cmsable.admin-redirect') {
            return $this->redirector->to($this->url->scope('admin')->to('/'));
        }

        if($page->getRedirectType() == SiteTreeNode::NONE){
            throw new HttpException(500);
        }

        if($page->getPath() == '_error_'){
            throw new HttpException(500);
        }

        return $this->redirector->to($this->url->to($page));

    }

    protected function getPageFromRequest(Request $request)
    {
        if (!$request instanceof CmsRequest) {
            throw new RuntimeException('Cant do cms redirects without an cms request');
        }

        return $request->getCmsPath()->getMatchedNode();
    }

}

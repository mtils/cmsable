<?php namespace Cmsable\Http;

use Symfony\Component\HttpFoundation\Request;

interface CmsPathCreatorInterface{

    /**
     * Creates CmsPath from Request $request
     *
     * @param \Illuminate\Http\Request
     * @return \Cmsable\Http\CmsPath
     **/
    public function createFromRequest(Request $request);

    /**
     * Creates a deacticated CmsPath which is marked as a "non-cms-path"
     *
     * @param \Illuminate\Http\Request
     * @return \Cmsable\Http\CmsPath
     **/
//     public function createDeactivated(Request $request);

}

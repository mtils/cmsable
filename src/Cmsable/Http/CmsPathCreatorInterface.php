<?php namespace Cmsable\Http;

use Symfony\Component\HttpFoundation\Request;

interface CmsPathCreatorInterface{

    /**
     * @return \Cmsable\Http\CmsPath
     **/
    public function createFromPath($originalPath);

    /**
     * @return \Cmsable\Http\CmsPath
     **/
    public function createFromRequest(Request $request);

    public function createDeactivated($originalPath);

}

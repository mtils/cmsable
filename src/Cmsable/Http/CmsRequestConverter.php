<?php


namespace Cmsable\Http;

use Illuminate\Http\Request;

class CmsRequestConverter
{

    /**
     * Creates a CmsRequest out of an Illuminate Request
     *
     * @param \Illuminate\Http\Request $request
     * @return \Cmsable\Http\CmsRequest
     **/
    public function toCmsRequest(Request $request)
    {

        $cmsRequest = (new CmsRequest)->duplicate(

            $request->query->all(), $request->request->all(), $request->attributes->all(),

            $request->cookies->all(), $request->files->all(), $request->server->all()
        );

        $cmsRequest->headers = clone $request->headers;

        return $cmsRequest;

    }

}
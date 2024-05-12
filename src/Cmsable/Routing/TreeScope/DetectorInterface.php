<?php namespace Cmsable\Routing\TreeScope;

use Illuminate\Http\Request;

interface DetectorInterface{

    /**
     * Return the scope of a tree model for request $request
     *
     * @param Request $request
     * @return TreeScope
     **/
    public function detectScope(Request $request);

}
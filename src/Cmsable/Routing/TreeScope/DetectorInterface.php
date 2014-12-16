<?php namespace Cmsable\Routing\TreeScope;

use Illuminate\Http\Request;

interface DetectorInterface{

    /**
     * Return the scope of a tree model for request $request
     *
     * @param \Illuminate\Http\Request $request
     * @return \Cmsable\Routing\TreeScope\TreeScope
     **/
    public function detectScope(Request $request);

}
<?php namespace Cmsable\Http;

interface CurrentCmsPathProviderInterface{

    public function getCurrentCmsPath($routeScope=NULL);

}
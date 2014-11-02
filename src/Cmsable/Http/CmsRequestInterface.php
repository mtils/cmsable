<?php namespace Cmsable\Http;

interface CmsRequestInterface{

    public function path();

    public function originalPath();

    public function getCmsPath();

    public function setCmsPath(CmsPath $path);


}
<?php namespace Cmsable\Resource\Contracts;

interface Distributor
{

    public function form($model=null, $resource=null);

    public function hasForm($resource=null);

    public function searchForm($resource=null);

    public function hasSearchForm($resource=null);

    public function validator($resource=null);

    public function rules($resource=null);

    public function getCurrentResource();

    public function setCurrentResource($resource);

}
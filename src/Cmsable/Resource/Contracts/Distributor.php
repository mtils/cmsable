<?php namespace Cmsable\Resource\Contracts;

interface Distributor
{

    public function form($model=null, $resource=null);

    public function searchForm($resource=null);

    public function rules($resource=null);

    public function getCurrentResource();

    public function setCurrentResource($resource);

}
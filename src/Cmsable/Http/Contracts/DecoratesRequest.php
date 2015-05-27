<?php namespace Cmsable\Http\Contracts;

use Illuminate\Http\Request;

interface DecoratesRequest
{

    public function decorate(Request $current);

    public function originalRequest();

}
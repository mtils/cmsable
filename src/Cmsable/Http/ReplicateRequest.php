<?php namespace Cmsable\Http;

use Illuminate\Http\Request;

trait ReplicateRequest
{

    protected $originalRequest;

    public function decorate(Request $current)
    {

        $files = $current->files->all();

        $files = is_array($files) ? array_filter($files) : $files;

        $this->initialize(
            $current->query->all(), $current->request->all(), $current->attributes->all(),
            $current->cookies->all(), $files, $current->server->all(), $current->getContent()
        );

        if ($session = $current->getSession())
            $this->setSession($session);

        $this->setUserResolver($current->getUserResolver());

        $this->setRouteResolver($current->getRouteResolver());

        $this->originalRequest = $current;
    }

    public function originalRequest()
    {
        return $this->originalRequest;
    }


}
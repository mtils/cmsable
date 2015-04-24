<?php namespace Cmsable\Auth;

use Auth;
use App;

use Permit\CurrentUser\ContainerInterface;

class PermitCurrentUserProvider implements CurrentUserProviderInterface{

    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function current(){
        return $this->container->user();
    }

}
<?php namespace Cmsable\Auth;

interface PermissionableInterface{
    public function isAllowed($permission, UserPermissionInterface $user);
}
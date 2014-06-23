<?php namespace Cmsable\Auth;

interface UserPermissionInterface{
    public function canAccess($permission);
}
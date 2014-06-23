<?php namespace Cmsable\Support\Sentry;

use Cmsable\Auth\CurrentUserProviderInterface;
use Sentry;
use Auth;
use App;

class SentryCurrentUserProvider implements CurrentUserProviderInterface{

    protected $authManager;

    protected $userModel;

    protected $fallBackUser;

    public function __construct($userModel){
        $this->userModel = $userModel;
    }

    public function current(){
        if($user = Sentry::getUser()){
            return $user;
        }
        return $this->getFallbackUser();
    }

    protected function getFallbackUser(){
        if(!$this->fallBackUser){
            $this->fallBackUser = App::make($this->userModel);
        }
        return $this->fallBackUser;
    }

}
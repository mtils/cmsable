<?php namespace Cmsable\Auth;

use Auth;
use App;

class LaravelCurrentUserProvider implements CurrentUserProviderInterface{

    protected $authManager;

    protected $userModel;

    protected $fallBackUser;

    public function __construct($userModel){
        $this->userModel = $userModel;
    }

    public function current(){
        if($user = Auth::user()){
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
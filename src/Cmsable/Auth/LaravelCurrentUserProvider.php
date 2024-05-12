<?php namespace Cmsable\Auth;

use Auth;
use App;
use Config;
use Illuminate\Foundation\Application;

class LaravelCurrentUserProvider implements CurrentUserProviderInterface{

    protected $authManager;

    protected $userModel;

    protected $fallBackUser;

    public function __construct(){
        $this->userModel = Application::getInstance()->make('config')->get('auth.model');
    }

    public function current(){
        if($user = Auth::user()){
            return $user;
        }
        return $this->getFallbackUser();
    }

    protected function getFallbackUser(){
        if(!$this->fallBackUser){
            $this->fallBackUser = Application::getInstance()->make($this->userModel);
        }
        return $this->fallBackUser;
    }

}
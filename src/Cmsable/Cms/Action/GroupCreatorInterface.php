<?php namespace Cmsable\Cms\Action;

interface GroupCreatorInterface{

    /**
     * @brief Creates a group, which will by filled bei actions
     *
     * @param $name The name of that group
     * @param mixed $resource The resource which is accessed/manipulated
     * @param string $context (default: 'default') In which context is it used
     * @return Cmsable\Action\Group
     **/
    public function createGroup($name='default');

    /**
     * @brief Create an array of groups
     *
     * @param mixed $user Your user object or null
     * @param mixed $resource Some resource which is accessed/manipulated
     * @param string $context (default: 'default') In which context is it used
     * @return array
     **/
    public function getGroups($user, $resource, $context='default');

}
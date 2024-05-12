<?php namespace Cmsable\Cms\Action;

class NamedGroupCreator implements GroupCreatorInterface{

    /**
     * @brief Creates a group, which will by filled bei actions
     *
     * @param $name The name of that group
     * @return Group
     **/
    public function createGroup($name='default'){

        $group = new Group();
        $group->setName($name);

        return $group;
    }

     /**
     * @brief Create an array of groups
     *
     * @param mixed $user Your user object or null
     * @param mixed $resource Some resource which is accessed/manipulated
     * @param string $context (default: 'default') In which context is it used
     * @return Group[]
     **/
    public function getGroups($user, $resource, $context='default'){
        return [$this->createGroup()];
    }
}
<?php namespace Cmsable\Routing\TreeScope;


/**
 * A Treescope is a value object which represents a connection between a
 * path, a model tree and an internal name for its usage
 *
 * The basic routing process of cmsable starts with the detection of any scopes
 * 
 * Example:
 * You could have your whole main cms seit under http://yourdomain/cms.
 * The path-prefix to your cms would be "cms".
 *
 * Then you could have an admin interface which is located under http://yourdomain/admin
 * The path-prefix to your admin interface would be "admin".
 *
 * If you store your cms trees inside a database you would connect the pathprefix
 * to an root_id column.
 *
 * Inside your code it is easier and more reliable to not depend on either use
 * the root_id nor the path_prefix, so like with named routes you have named
 * scopes. This allows:
 * @example Url::scope('default')->route('users.index')
 * 
 */
class TreeScope{

    /**
     * The name of the default (fallback) scope
     *
     * @var string
     **/
    const DEFAULT_NAME = 'default';

    /**
     * The scope name of an admin interface tree
     *
     * @var string
     **/
    const ADMIN_NAME = 'admin';

    /**
     * The root id of a tree model
     *
     * @var int
     **/
    protected $modelRootId = 0;

    /**
     * The unique name of this scope
     *
     * @var string
     **/
    protected $name = 'default';

    /**
     * An optional path-prefix to all paths inside this scope
     *
     * @var string
     **/
    protected $pathPrefix = '/';

    /**
     * A title for this scope to allow selecting via a cms gui
     *
     * @var string
     **/
    protected $title = '';

    /**
     * Returns a root or scope Id of this tree. If you use a tree database
     * model this would be the root id of your tree. But it could also be
     * a top directory name if you organise your trees inside a filesystem
     *
     * @return int
     **/
    public function getModelRootId(){
        return $this->modelRootId;
    }

    /**
     * Set a root or scope id for this scope. A model can take this id to
     * provide a distinct tree from a database
     *
     * @param int $id
     * @return self
     **/
    public function setModelRootId($rootId){
        $this->modelRootId = $rootId;
        return $this;
    }

    /**
     * A unique and code-readable name of this scope. This should be neither
     * the modelRootId nor a pathPrefix. It could be a path prefix without
     * slashes but the purpose of this is to habe a internal name like a named
     * route to allow code like URL::scope('default')->to() or
     * URL::scope('admin')->to without touching any model or path depending
     * areas.
     *
     * @return string
     **/
    public function getName(){
        return $this->name;
    }

    /**
     * Set a unique code-readable name for this scope
     *
     * @see self::getName()
     * @param string $name
     * @return self
     **/
    public function setName($name){
        $this->name = $name;
        return $this;
    }

    /**
     * Return a path prefix for this scope. A path prefix is the first segment
     * of an url path (http://yourdomain.com/admin/dashboard would be admin)
     *
     * @return string
     **/
    public function getPathPrefix(){
        return $this->pathPrefix;
    }

    /**
    * Set a prefix for paths inside this tree
    * 
    * @see self::getPathPrefix()
    * @param string url prefix
    * @return self
    */
    public function setPathPrefix($prefix){
        $this->pathPrefix = $prefix;
        return $this;
    }

    /**
     * Return a human-readable title for this scope
     *
     * @return string
     **/
    public function getTitle(){
        return $this->title;
    }

    /**
     * Set a human-readable title for this scope
     *
     * @param string $title
     * @return self
     **/
    public function setTitle($title){
        $this->title = $title;
        return $this;
    }

    /**
     * Overloaded accessor access.
     *
     * @string $property
     * @return mixed
     **/
    public function __get($property){
        return call_user_func([$this, "get$property"]);
    }

    /**
     * Overloaded mutator access
     *
     * @param string $property
     * @param mixed $value
     * @return void
     **/
    public function __set($property, $value){
        call_user_func([$this, "set$property"], $value);
    }

    /**
     * String representation of this scope. Makes some checks easier
     * (if (string)CMS::currentScope() == 'admin')
     *
     * @return string
     **/
    public function __toString(){
        return $this->getName();
    }

}
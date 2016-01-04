<?php namespace Cmsable\PageType;

use App;
use Closure;
use Versatile\Introspection\Contracts\DescriptionIntrospector as Namer;

class PageType
{

    /**
     * @var \Closure
     **/
    protected static $viewBootstrap;

    protected static $bootstrapCalled = false;

    protected $id;

    protected $_controllerClassName;

    protected $_singularName;

    protected $_pluralName;

    protected $_description;

    protected $_cmsIcon;

    protected $_allowdChildren;

    protected $_allowedParents;

    protected $_category;

    protected $_canBeRoot;

    protected $_controllerCreatorClass = 'Cmsable\Routing\ControllerCreator';

    protected $_formPluginClass;

    protected $_controllerCreator;

    protected $_formPlugin;

    protected $_routeScope = 'default';

    protected $_targetPath = '';

    protected $_routeNames = [];

    protected $langKey;

    public function __construct($id=NULL){
        $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
        return $this;
    }

    public function getControllerClass(){
        return $this->_controllerClassName;
    }

    public function controllerClassName(){
        return $this->_controllerClassName;
    }

    public function setControllerClassName($className){
        $this->_controllerClassName = $className;
        return $this;
    }

    public function getTargetPath(){
        return $this->_targetPath;
    }

    public function setTargetPath($routePath){
        $this->_targetPath = $routePath;
        return $this;
    }

    public function singularName(){
        static::callBootstrap();
        return $this->_singularName;
    }

    public function setSingularName($name){
        $this->_singularName = $name;
        return $this;
    }

    public function pluralName(){
        static::callBootstrap();
        return $this->_pluralName;
    }

    public function setPluralName($name){
        $this->_pluralName = $name;
        return $this;
    }

    public function description(){
        static::callBootstrap();
        return $this->_description;
    }

    public function setDescription($description){
        $this->_description = $description;
        return $this;
    }

    public function cmsIcon(){
        return $this->_cmsIcon;
    }

    public function setCmsIcon($icon){
        $this->_cmsIcon = $icon;
        return $this;
    }

    public function allowedChildren(){
        return $this->_allowdChildren;
    }

    public function setAllowedChildren(array $children){
        $this->_allowdChildren = $children;
        return $this;
    }

    public function allowedParents(){
        return $this->_allowedParents;
    }

    public function setAllowedParents($parents){
        $this->_allowedParents = $parents;
        return $this;
    }

    public function category(){
        return $this->_category;
    }

    public function setCategory($category){
        $this->_category = $category;
        return $this;
    }

    public function canBeRoot(){
        return $this->_canBeRoot;
    }

    public function setCanBeRoot($canBe){
        $this->_canBeRoot = $canBe;
        return $this;
    }

    public static function create($id){
        $class = get_called_class();
        return new $class($id);
    }

    public function getRouteScope(){
        return $this->_routeScope;
    }

    public function setRouteScope($scopeId){
        $this->_routeScope = $scopeId;
        return $this;
    }

    public function createController($page){
        return $this->getControllerCreator()->createController($this->controllerClassName(), $page);
    }

    public function getControllerCreator(){
        if(!$this->_controllerCreator){
            $this->_controllerCreator = App::make($this->getControllerCreatorClass());
        }
        return $this->_controllerCreator;
    }

    public function setControllerCreator($creator){
        $this->_controllerCreator = $creator;
        return $this;
    }

    public function getControllerCreatorClass(){
        return $this->_controllerCreatorClass;
    }

    public function setControllerCreatorClass($class){
        $this->_controllerCreatorClass = $class;
        return $this;
    }

    public function getFormPlugin(){
        if($this->_formPlugin === NULL){
            $this->_formPlugin = App::make($this->getFormPluginClass());
            $this->_formPlugin->setPageType($this);
        }
        return $this->_formPlugin;
    }

    public function getFormPluginClass(){
        return $this->_formPluginClass;
    }

    public function setFormPluginClass($className){
        $this->_formPluginClass = $className;
        return $this;
    }

    public function getRouteNames()
    {
        return $this->_routeNames;
    }

    public function setRouteNames($routeNames)
    {
        $this->_routeNames = (array)$routeNames;
        return $this;
    }

    public function getLangKey()
    {
        return $this->langKey;
    }

    public function setLangKey($langKey)
    {
        $this->langKey = $langKey;
        return $this;
    }

    public static function getViewBootstrap()
    {
        return static::$viewBootstrap;
    }

    /**
     * Naming of pagetypes it deferred to the point where someone asks for
     * a title, description or icon of a pagetype. Otherwise the whole stuff
     * would be done on every request.
     * So the first time some title, icon or description is asked for this
     * closure will be called one time.
     *
     * @param \Closure $bootstrap
     * @return void
     **/
    public static function setViewBootstrap(Closure $bootstrap)
    {
        static::$viewBootstrap = $bootstrap;
    }

    protected static function callBootstrap()
    {
        if (static::$bootstrapCalled) {
            return;
        }

        static::$bootstrapCalled = true;

        if ($bootstrap = static::$viewBootstrap) {
            $bootstrap();
        }

    }
}
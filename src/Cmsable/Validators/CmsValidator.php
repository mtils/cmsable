<?php namespace Cmsable\Validators;

use Cmsable\Model\SiteTreeModelInterface;
use Cmsable\Cms\SiteTreeNodeInterface;
use Illuminate\Validation\Validator;
use Illuminate\Routing\Router;
use DomainException;
use ReflectionClass;
use ReflectionMethod;
use UnexpectedValueException;

class CmsValidator extends Validator{

    protected $siteTreeLoaders;

    protected $router;

    public function validateUrlSegment($attribute, $value, $parameters){
        return preg_match('/^[\pL\pN-]+$/u', $value);
    }

    public function addSiteTreeLoader(SiteTreeModelInterface $loader){
        $this->siteTreeLoaders[] = $loader;
        return $this;
    }

    public function setRouter(Router $router){
        $this->router = $router;
        return $this;
    }

    public function validateUniqueSegmentOf($attribute, $value, $parameters){

        if(!$this->siteTreeLoaders){
            throw new DomainException('Validating unique_segment_of requires a SiteTreeModelInterface instance');
        }

        $this->requireParameterCount(2, $parameters, 'unique_segment_of');

        // Get Parent
        $parentPath = NULL;
        if(!$parentId = $this->getValue($parameters[0])){
            throw new UnexpectedValueException('Missing parent_id parameter or value');
        }

        // Get the id of the currently edited page
        $editedPageId = $this->getValue($parameters[1]);

        // Cast the id for better comparison
        if(is_numeric($editedPageId) && $editedPageId){
            $editedPageId = (int)$editedPageId;
        }

        // Find the corresponding sitetreeloader (admin/public)
        // And the parent path of this node
        $containingLoader = NULL;
        foreach($this->siteTreeLoaders as $loader){
            if($parentPath = $loader->pathById($parentId)){
                $containingLoader = $loader;
                break;
            }
        }
        if(!$parentPath){
            throw new UnexpectedValueException('Can\'t find desired parent of node');
        }

        // Build the desired path
        $path = trim("$parentPath/$value",'/');

        // Look for pages with this path
        $pageWithThisPath = $containingLoader->pageByPath($path);
        if($pageWithThisPath instanceof SiteTreeNodeInterface){
            $pk = $pageWithThisPath->getKeyName();
            // The page with this id is the currently edited
            if($pageWithThisPath->__get($pk) == $editedPageId){
                return TRUE;
            }
            // Another page has the same path
            else{
                return FALSE;
            }
        }
        return TRUE;
    }

    public function validateNoManualRoute($attribute, $value, $parameters){

        if(!$this->router){
            throw new DomainException('Validating no_manual_route requires a Router instance');
        }
        if(!$this->siteTreeLoaders){
            throw new DomainException('Validating no_manual_route requires a SiteTreeModelInterface instance');
        }

        $this->requireParameterCount(1, $parameters, 'validate_no_manual_route');

        // Get Parent (to build parent path)
        $parentPath = NULL;
        if(!$parentId = $this->getValue($parameters[0])){
            throw new UnexpectedValueException('Missing parent_id parameter or value');
        }

        // Find the corresponding sitetreeloader (admin/public)
        // And the parent path of this node
        $containingLoader = NULL;
        foreach($this->siteTreeLoaders as $loader){
            if($parentPath = $loader->pathById($parentId)){
                $containingLoader = $loader;
                break;
            }
        }
        if(!$parentPath){
            throw new UnexpectedValueException('Can\'t find desired parent of node');
        }

        // Build the desired path
        $path = trim("$parentPath/$value",'/');

        $uris = array();

        foreach($this->router->getRoutes() as $route){
            $uri = $route->getUri();
            $paramPos = mb_strpos($uri,'{');
            if($paramPos !== FALSE){
                $uri = trim(mb_substr($uri,0,$paramPos),'/');
            }
            $uriStack = explode('/',$uri);
            $cleanedUri = trim(implode('/', $uriStack),'/');

            if(trim($cleanedUri) != '' && !in_array($cleanedUri,$uris)){
                $uris[] = $cleanedUri;
            }
        }
        return !in_array($path, $uris);
    }
}
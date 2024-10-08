<?php namespace Cmsable\Cms\Action;

use Collection\Table\Table;
use Traversable;

class ClassResourceTypeIdentifier implements ResourceTypeIdentifierInterface{

    /**
     * @brief Returns an id for an resourcetype to identify it
     *
     * @param mixed $resource
     * @return string
     **/
    public function identifyItem($resource){

        // If object passed
        if(is_object($resource)){

            $className = get_class($resource);

            if ($className == 'stdClass') {
                if (isset($resource->className)) {
                    return $resource->className;
                }
            }

            return $className;

        }

        // If classnames passed
        elseif(is_string($resource)){
            return ltrim($resource,'\\');
        }

        // otherwise just return the php internal typename
        return gettype($resource);

    }

    /**
     * @brief Returns an id for a collection
     *
     * @param Traversable $resource
     * @return string
     **/
    public function identifyCollection($resource){

        if(is_array($resource) || $resource instanceof Traversable){
            foreach($resource as $item){
                return $this->identifyItem($item);
            }

        }

        if (!is_object($resource)) {
            return gettype($resource);
        }

        // empty result || Collection\Table\Table
        if (isset($resource->itemClass)) {
            return $resource->itemClass;
        }

        if (method_exists($resource, 'modelClass')) {
            return $resource->modelClass();
        }

        return gettype($resource);

    }

}
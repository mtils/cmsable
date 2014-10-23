<?php namespace Cmsable\Cms\Action;

class ClassResourceTypeIdentifier implements ResourceTypeIdentifierInterface{

    /**
     * @brief Returns an id for an resourcetype to identify it
     *
     * @param mixed $resourceType 
     * @return string
     **/
    public function identify($resource){

        // If object passed
        if(is_object($resource)){

            $className = get_class($resource);

            if($className == 'stdClass'){
                if(isset($resource->className)){
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

}
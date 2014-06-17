<?php namespace Cmsable\Html;

use ArrayIterator;

class FilteredChildIterator extends ArrayIterator{

    protected $filter = NULL;

    public static function create($childNodes, $filter){
        $newArray = array();
        foreach($childNodes as $node){
            if($filter->isVisible($node)){
                $newArray[] = $node;
            }
        }
        return new FilteredChildIterator($newArray);
    }
}
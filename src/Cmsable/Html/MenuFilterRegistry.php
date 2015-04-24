<?php namespace Cmsable\Html;


use Cmsable\Model\SiteTreeNodeInterface;

class MenuFilterRegistry
{

    /**
     * Filters with a name
     *
     * @var array
     **/
    protected $namedFilters = [];

    /**
     * Filters which will be triggered every time
     *
     * @var array
     **/
    protected $globalFilters = [];

    /**
     * Adds a filter named $name to the menu.
     * The filter has to be callable. If youre filtered should be triggered all
     * all the time (e.g. auth checks) pass '*' as a name
     *
     * @param string $name
     * @param callable $filter
     **/
    public function filter($name, callable $filter)
    {

        if ($name == '*') {
            $this->globalFilters[] = $filter;
            return $this;
        }

        $this->namedFilters[$name][] = $filter;

        return $this;

    }

    public function clear($name)
    {
        $this->namedFilters[$name] = [];
    }

    public function filteredChildren($childNodes, $filterName='default'){

        $filtered = array();

        foreach($childNodes as $node){
            if($this->isVisible($node, $filterName)){
                $filtered[] = $node;
            }
        }

        return $filtered;

    }

    public function isVisible(SiteTreeNodeInterface $page, $filterName='default')
    {

        foreach ($this->getFilters($filterName) as $filter) {
            if (!$filter($page)) {
                return false;
            }
        }

        return true;
    }

    public function getFilters($name)
    {
        $namedFilters = isset($this->namedFilters[$name])
                        ? $this->namedFilters[$name]
                        : [];

        return array_merge($namedFilters, $this->globalFilters);

    }

}
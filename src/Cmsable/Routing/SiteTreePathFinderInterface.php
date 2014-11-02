<?php namespace Cmsable\Routing;

use Cmsable\Cms\Action\Action;

interface SiteTreePathFinderInterface{

    const NEAREST = 'nearest';

    const HIGHEST = 'highest';

    const DEEPEST = 'deepest';

    const PARENTS = 'parents';

    const CHILDS = 'childs';

    public function toPage($pageOrId);

    public function toRoutePath($path, $searchMethod=self::NEAREST);

    public function toRouteName($name, $searchMethod=self::NEAREST);

    public function toPageType($pageType, $searchMethod= self::NEAREST);

    public function toControllerAction($action);

    public function toCmsAction(Action $action);

}
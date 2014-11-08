<?php namespace Cmsable\Routing;

use Cmsable\Cms\Action\Action;

interface SiteTreePathFinderInterface{

    const NEAREST = 'nearest';

    const HIGHEST = 'highest';

    const DEEPEST = 'deepest';

    const PARENTS = 'parents';

    const CHILDS = 'childs';

    public function toPage($pageOrId);

    public function toRoutePath($path, array $params=[], $searchMethod=self::NEAREST);

    public function toRouteName($name, array $params=[], $searchMethod=self::NEAREST);

    public function toPageType($pageType, array $params=[], $searchMethod= self::NEAREST);

    public function toControllerAction($action, array $params=[], $searchMethod= self::NEAREST);

    public function toCmsAction(Action $action, array $params=[], $searchMethod= self::NEAREST);

}
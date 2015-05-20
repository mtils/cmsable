<?php namespace Cmsable\Model;

use Illuminate\Database\Eloquent\Model;
use Cmsable\PageType\DBConfigRepository;
use Cmsable\Html\MenuFilterRegistry;

class PageQueryFactory
{

    protected $pageModel;

    protected $configRepo;

    protected $menuFilters;

    public function __construct(Model $pageModel, DBConfigRepository $configRepo,
                                MenuFilterRegistry $menuFilters)
    {
        $this->pageModel = $pageModel;
        $this->configRepo = $configRepo;
        $this->menuFilters = $menuFilters;
    }

    public function newQuery()
    {
        return new PageQuery(
            $this->pageModel->newQuery(),
            $this->configRepo,
            $this->menuFilters
        );
    }

    public function __call($method, array $params=[])
    {
        $query = $this->newQuery();
        call_user_func_array([$query,$method], $params);
        return $query;
    }

}
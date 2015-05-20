<?php namespace Cmsable\Model;

use Illuminate\Database\Eloquent\Builder;
use Cmsable\PageType\DBConfigRepository;
use Cmsable\PageType\PageType;
use Cmsable\Html\MenuFilterRegistry;

class PageQuery 
{

    public $wheres = [];

    public $orderBys;

    public $offset;

    public $limit;

    public $withHidden;

    protected $configRepo;

    protected $pageTypeId;

    protected $query;

    protected $configJoinAdded = false;

    protected $addedJoinsCounter = 0;

    protected $configType;

    protected $menuFilters;

    public function __construct(Builder $query, DBConfigRepository $configRepo,
                                MenuFilterRegistry $menuFilters)
    {
        $this->query = $query;
        $this->configRepo = $configRepo;
        $this->menuFilters = $menuFilters;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key The key name
     * @param mixed $operatorOrValue value if $value is null
     * @param mixed $value (optional)
     * @return self
     **/
    public function where($key, $operatorOrValue, $value=null)
    {

        if ($key == 'page_type' && $value === null) {
            return $this->pageType($operatorOrValue);
        }

        if ($this->isPageKey($key)) {
            $this->query->where($key, $operatorOrValue, $value);
            return $this;
        }

        if ($this->isConfigKey($key)) {
            return $this->addConfigWhere($key, $operatorOrValue, $value);
        }

        return $this;

    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $order
     * @return self
     **/
    public function orderBy($key, $order='asc')
    {
        if ($this->isConfigKey($key) && $this->addedJoinsCounter != 0) {
            $this->query->orderBy('config.'.$this->configQueryKey($key), $order);
            return $this;
        }

        $this->query->orderBy($key, $order);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $offset
     * @return self
     **/
    public function offset($offset)
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $limit
     * @return self
     **/
    public function limit($limit)
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param Cmsable\PageType\PageType|string pagetype or its id
     * @return self
     **/
    public function pageType($typeOrId)
    {
        $this->pageTypeId = $typeOrId instanceof PageType ? $typeOrId->getId() : $typeOrId;
        $this->query->where('page_type', $this->pageTypeId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $hidden
     * @return self
     **/
    public function withHidden($hidden=true)
    {
        $this->withHidden = $hidden;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Traversable
     **/
    public function get()
    {

        $result = $this->query->select([$this->pageTable().'.*'])->get();

        if ($this->withHidden) {
            return $result;
        }

        return $this->menuFilters->filteredChildren($result,'');

    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     **/
    public function query()
    {
        return $this->query;
    }

    protected function addConfigWhere($key, $operatorOrValue, $value=null)
    {
        $alias = $this->addNextConfigJoin();
        $this->query->where($this->configVarnameColumn($alias), $key);
        $this->query->where(
            "$alias.".$this->configQueryKey($key),
            $operatorOrValue,
        $value);
        return $this;
    }

    protected function isConfigKey($key)
    {

        if ($this->isPageKey($key)) {
            return false;
        }

        if (starts_with($key,'config.')) {
            return true;
        }

        if (!$configType = $this->configType()) {
            return false;
        }

        if (isset($configType[$key])) {
            return true;
        }

    }

    protected function isPageKey($key)
    {
        if (strpos('.',$key)) {
            return false;
        }

        return in_array($key, [
            'id',
            'root_id',
            'page_type',
            'url_segment',
            'title',
            'menu_title',
            'visibility',
            'redirect_type',
            'redirect_target',
            'parent_id',
            'position',
            'view_permission',
            'edit_permission'
        ]);
    }

    protected function configQueryKey($key)
    {
        if (starts_with($key, 'config.')) {
            return $key;
        }

        if (!$configType = $this->configType()) {
            return $key;
        }

        if (!isset($configType[$key])) {
            return $key;
        }

        return  $this->configRepo->getColumnOfXType($configType[$key]);
    }

    protected function configVarnameColumn($alias)
    {
        return "$alias.".$this->configRepo->getColumn('varname');
    }

    protected function addNextConfigJoin()
    {

        $alias = $this->addedJoinsCounter > 0 ? 'config'. $this->addedJoinsCounter : 'config';

        $pageKeyName = $this->query->getModel()->getKeyName();
        $pageTable = $this->pageTable();
        $configTable = $this->configRepo->getTableName();
        $foreignKey = $this->configRepo->getColumn('page_id');

        $this->query->leftJoin(
            "$configTable AS $alias",
            "$alias.$foreignKey",
            '=',
            "$pageTable.$pageKeyName"
        );
        $this->query->distinct();

        $this->addedJoinsCounter++;

        return $alias;

    }

    protected function pageTable()
    {
        return $this->query->getModel()->getTable();
    }

    protected function configType()
    {

        if ($this->configType) {
            return $this->configType;
        }

        if (!$this->pageTypeId) {
            return;
        }

        $this->configType = $this->configRepo->getConfigtype($this->pageTypeId);

        return $this->configType;
    }

}
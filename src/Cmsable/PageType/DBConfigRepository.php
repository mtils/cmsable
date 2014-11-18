<?php namespace Cmsable\PageType;

use DateTime;
use OutOfBoundsException;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

use XType\AbstractType;
use XType\NamedFieldType;

class DBConfigRepository implements ConfigRepositoryInterface{

    protected $typeRepository;

    protected $tableName = 'pagetype_configs';

    protected static $resolver;

    protected $connectionName;

    protected $columnNames = [

        'id'           => 'id',
        'page_type_id' => 'page_type_id',
        'varname'      => 'varname',
        'page_id'      => 'page_id',
        'boolean'      => 'int_val',
        'integer'      => 'int_val',
        'float'        => 'float_val',
        'temporal'     => 'int_val',
        'string'       => 'string_val',
        'blob'         => 'string_val'

    ];

    public function __construct(ConfigTypeRepositoryInterface $typeRepo){

        $this->typeRepository = $typeRepo;

    }

    public function getTableName(){
        return $this->tableName;
    }

    public function setTableName($name){
        $this->tableName = $name;
        return $this;
    }

    public function getColumn($type){

        if(isset($this->columnNames[$type])){
            return $this->columnNames[$type];
        }

    }

    public function setColumn($type, $column){

        if(!isset($this->columnNames[$type])){
            throw new OutOfBoundsException("Type $type unknown");
        }

        $this->columnNames[$type] = $column;

        return $this;

    }

    public function makeConfig($pageType){

        $configType = $this->typeRepository->getConfigType($pageType);

        $config = new Config();

        $pageTypeId = $this->pageTypeId($pageType);

        $config->setPageTypeId($pageTypeId);

        foreach($configType as $key=>$type){

            $config->setFromModel($key, $type->getDefaultValue());

        }

        return $config;

    }

    public function getConfig($pageType, $pageId=null){

        $config = $this->makeConfig($pageType);

        $pageTypeId = $this->pageTypeId($pageType);

        if($result = $this->getRawResult($pageTypeId, $pageId)){

            $this->fillConfigByDbResult($config, $result);

        }

        return $config;

    }

    public function saveConfig(ConfigInterface $config, $pageId=null){

        $configType = $this->typeRepository->getConfigType($config->getPageTypeId());

        $rows = array();

        if(!$this->configDidChange($config, $configType)){
            return $this;
        }

        if(!$rows = $this->buildSavableRows($config, $configType, $pageId)){
            return $this;
        }

        $this->deleteConfig($config, $pageId);

        foreach($rows as $row){
            $this->getConnection()->table($this->getTableName())->insert($row);
        }

        // Doesnt work under Laravel 4.1, but docs say (http://laravel.com/docs/queries#inserts)
        // $this->con->table($this->getTableName())->insert($rows);

        return $this;

    }

    public function deleteConfig($configOrPageType, $pageId=null){

        $pageTypeId = $this->pageTypeId($configOrPageType);

        $this->getMergedQuery($pageTypeId, $pageId)->delete();

        return $this;
    }

    protected function buildSavableRows(ConfigInterface $config, NamedFieldType $configType, $pageId){

        $rows = [];

        foreach($configType as $key=>$type){

            $values = [
                $this->getColumn('page_type_id') => $config->getPageTypeId(),
                $this->getColumn('varname')      => $key
            ];

            if($pageId){
                $values[$this->getColumn('page_id')] = $pageId;
            }

            $fieldName = $this->getColumnOfXType($type);

            $values[$fieldName] = $this->castToDatabase($type, $config->get($key));

            $rows[] = $values;

        }

        return $rows;

    }

    protected function configDidChange(ConfigInterface $config, NamedFieldType $type){

        foreach($type as $key=>$typeData){
            if($config->hasChanged($key)){
                return true;
            }
        }

        return false;

    }

    protected function getRawResult($pageTypeId, $pageId=NULL){

        $query = $this->getMergedQuery($pageTypeId, $pageId);
        $query = $query->orderBy($this->getColumn('page_id'),'asc');

        return $query->get();

    }

    protected function getMergedQuery($pageTypeId, $pageId=NULL){

        $query = $this->getConnection()->table($this->tableName);

        $query = $query->where($this->getColumn('page_type_id'), $pageTypeId);

        if($pageTypeId){
            $query = $query->where($this->getColumn('page_id'), $pageId)
                           ->orWhereNull($this->getColumn('page_id'));
        }
        else{
            $query = $query->whereNull($this->getColumn('page_id'));
        }

        return $query;

    }

    protected function fillConfigByDbResult(Config &$config, $dbResult){

        $configType = $this->typeRepository->getConfigType($config->getPageTypeId());

        foreach($dbResult as $row){

            $varname = $row->varname;

            try{

                $type = $configType->get($varname);

                $fieldName = $this->getColumnOfXType($type);

                $value = $this->castFromDatabase($type, $row->{$fieldName});

                $config->setFromModel($varname, $value);

            }
            // Field in DB does not exist in config
            catch(OutOfBoundsException $e){
                continue;
            }
        }

        return $config;

    }

    protected function getColumnOfXType(AbstractType $type){

        return $this->getColumn($this->getTypeNameOfXType($type));

    }

    protected function getTypeNameOfXType(AbstractType $type){

        switch($type->getGroup()){

            case AbstractType::BOOL:
                return 'boolean';

            case AbstractType::NUMBER:

                if($type->getNativeType() == 'int'){
                    return 'integer';
                }

                return 'float';

            case AbstractType::TEMPORAL:

                return 'temporal';

            default:

                return 'string';
        }
    }

    protected function castFromDatabase(AbstractType $type, $value){

        if($this->mustSerialize($type)){
            return unserialize($value);
        }
        switch($type->getGroup()){

            case AbstractType::BOOL:

                if($value == 1){
                    return TRUE;
                }
                return FALSE;

            case AbstractType::NUMBER:

                if($type->getNativeType() == 'int'){
                    return (int)$value;
                }

                return (float)$value;

            case AbstractType::TEMPORAL:

                return new DateTime($value);

            default:

                return "$value";
        }
    }

    protected function castToDatabase(AbstractType $type, $value){

        if($this->mustSerialize($type)){
            return serialize($value);
        }

        switch($type->getGroup()){

            case AbstractType::BOOL:

                if($value){
                    return 1;
                }
                return 0;

            case AbstractType::NUMBER:

                if($type->getNativeType() == 'int'){
                    return (int)$value;
                }
                return (float)$value;

            case AbstractType::TEMPORAL:

                if($value instanceof DateTime){
                    return $value->format(DateTime::ISO8601);
                }
                if(is_numeric($value)){
                    return date('Y-m-d H:i:s', (int)$value);
                }
                return "$value";

            default:
                return "$value";

        }
    }

    protected function mustSerialize(AbstractType $type){

        switch($type->getGroup()){
            case AbstractType::COMPLEX:
            case AbstractType::MIXED:
                return TRUE;
            default:
                return FALSE;
        }
    }

    protected function pageTypeId($pageType){

        if($pageType instanceof PageType){
            return $pageType->getId();
        }

        if($pageType instanceof Config){
            return $pageType->getPageTypeId();
        }

        return $pageType;
    }

    public function getTypeRepository(){
        return $this->typeRepository;
    }

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->connectionName);
    }

    /**
     * Resolve a connection instance.
     *
     * @param  string  $connection
     * @return \Illuminate\Database\Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

        /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

}
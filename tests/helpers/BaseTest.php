<?php

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/Migration.php';


abstract class BaseTest extends TestCase {

    /**
     * @brief Wenn das hier auf TRUE gesetzt ist, wird ein mal
     *        für die gesamte Klasse eine Datenbank mit migrate
     *        und seed erzeugt.
     **/
    protected static $requiresDatabase=FALSE;

    /**
     * @brief Das ist eine Fake SQLite Verbindung in eine leere :memory:
     *        Datenbank. Für viele Tests reicht die und ist aus Performance
     *        Gründen der kompletten DB vorzuziehen.
     **/
    protected $useStubConnection=FALSE;

    protected static $connection;

    private static $migrated = FALSE;

    private static $seeded = FALSE;

    public static function setUpBeforeClass(): void
    {

        if(static::$requiresDatabase){
            self::$connection = static::createConnection('tests');
        }

    }

    protected static function createConnection($name){

        $config = array(
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        );

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection = new SQLiteConnection($pdo, $name, '', $config);
//         $connection->setFetchMode(PDO::FETCH_CLASS);

        return $connection;
    }

    public function setUp(): void
    {

        parent::setUp();

        if(!static::$requiresDatabase && $this->useStubConnection){
            $this->injectConnection(static::createConnection('stub'),'stub');
        }
        elseif(static::$requiresDatabase){
            if(!self::$migrated){
                $this->injectConnection(self::$connection,'tests');
                self::migrateDatabase();
                self::seedDatabase();
                self::$migrated = TRUE;
            }
            $this->injectConnection(self::$connection,'tests');

        }

    }

    protected static function migrateDatabase(){
        $migration = new ManualMigrator(self::$connection);
        $migration->run();
    }

    protected static function seedDatabase(){

    }

    protected function injectConnection($connection, $name){

        $config = array(
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        );

        $this->app['config']["database.connections.$name"] = $config;

        $this->app['config']['database.default'] = $name;

//         DB::extend($name, function() use ($connection){
//             return $connection;
//         });

    }

    public function getResolver(){

        $res = new ConnectionResolver;
        $res->addConnection('tests',self::$connection);
        $res->setDefaultConnection('tests');
        return $res;

    }

    public function mock($class){

        $mock = Mockery::mock($class);
        return $mock;

    }

    public function newTestModel()
    {
        return new TestModel;
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

}

class TestModel extends Model{}

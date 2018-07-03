<?php 

use Mockery as m;

use XType\NamedFieldType;
use XType\StringType;
use XType\NumberType;
use XType\TemporalType;
use XType\SequenceType;
use XType\BoolType;

use Cmsable\PageType\DBConfigRepository;
use Cmsable\PageType\ManualConfigTypeRepository;


class DBConfigRepositoryFunctionalTest extends BaseTest{

    protected static $requiresDatabase=TRUE;

    public function testImplementsInterface(){


        $repo = $this->getRepo();

        $this->assertInstanceOf('Cmsable\PageType\ConfigRepositoryInterface', $repo);

    }

    public function testGetConfigReturnsDefaultIfNonInDb(){

        $repo = $this->getRepo();
        $typeRepo = $repo->getTypeRepository();
        $pageTypeId = 'pagetype';

        $configType = $this->getConfigType();

        $typeRepo->setConfigType($pageTypeId, $configType);

        $config = $repo->getConfig($pageTypeId);

        $this->assertInstanceOf('Cmsable\PageType\ConfigInterface', $config);

        foreach($configType as $key=>$type){

            $this->assertEquals($config->get($key),$type->getDefaultValue());

        }

    }

    public function testSaveConfig(){

        $repo = $this->getRepo();
        $typeRepo = $repo->getTypeRepository();
        $pageTypeId = 'pagetype';

        $configType = $this->getConfigType();

        $typeRepo->setConfigType($pageTypeId, $configType);

        $config = $repo->makeConfig($pageTypeId);

        $date = new DateTime('2013-10-10 22:00:00');

        $config->set('boolean', false);
        $config->set('integer', 44);
        $config->set('float', 4.3);
        $config->set('temporal', $date);
        $config->set('string','Test-String');

        $repo->saveConfig($config);

        $loadedConfig = $repo->getConfig($pageTypeId);

        $this->assertFalse($loadedConfig->get('boolean'));
        $this->assertEquals($loadedConfig->get('integer'),44);
        $this->assertEquals($loadedConfig->get('float'),4.3);
        $this->assertEquals($loadedConfig->get('temporal'),$date);
        $this->assertEquals($loadedConfig->get('string'),'Test-String');

        return $repo;

    }

    public function testDeleteConfigReturnsDefault(){

        $repo = $this->testSaveConfig();
        $pageTypeId = 'pagetype';

        $repo->deleteConfig('pagetype');

        // See if the values are default values again
        $configType = $this->getConfigType();

        $config = $repo->getConfig($pageTypeId);

        foreach($configType as $key=>$type){

            $configValue = $config->get($key);
            $defaultValue = $type->getDefaultValue();

            if ($configValue instanceof \DateTime) {
                $this->assertEquals($defaultValue->getTimestamp(), $configValue->getTimestamp());
                continue;
            }
            $this->assertEquals($config->get($key),$type->getDefaultValue());

        }

        $this->assertEquals(0, self::$connection->table('pagetype_configs')->count());
    }

    protected function getConfigType(){

        $configType = NamedFieldType::create();

        $date = new DateTime;

        $configType->set('boolean', BoolType::create()->setDefaultValue(true));
        $configType->set('integer', NumberType::create()->setNativeType('int')->setDefaultValue(22));
        $configType->set('float', NumberType::create()->setNativeType('float')->setDefaultValue(3.4));
        $configType->set('temporal', TemporalType::create()->setDefaultValue($date));
        $configType->set('string', StringType::create()->setDefaultValue('default'));

        return $configType;

    }

    protected function getRepo(){

        DBConfigRepository::setConnectionResolver($this->getResolver());

        $typeRepo = new ManualConfigTypeRepository;

        return new DBConfigRepository($typeRepo);

    }

    protected function getTypeRepo(){
        return m::mock('Cmsable\PageType\ConfigTypeRepositoryInterface');
    }

}

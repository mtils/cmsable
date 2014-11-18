<?php 

use Mockery as m;
use Cmsable\PageType\DBConfigRepository;


class DBConfigRepositoryTest extends PHPUnit_Framework_TestCase{

    public function testImplementsInterface(){

        $typeRepo = $this->getTypeRepo();
        $repo = new DBConfigRepository($typeRepo);

        $this->assertInstanceOf('Cmsable\PageType\ConfigRepositoryInterface', $repo);

    }

    protected function getTypeRepo(){
        return m::mock('Cmsable\PageType\ConfigTypeRepositoryInterface');
    }

}
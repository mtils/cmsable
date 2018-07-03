<?php 

use Mockery as m;
use Cmsable\Model\ArraySiteTreeModel;

class ArraySiteTreeModelTest extends BaseTest
{

    public function testImplementsInterfaces()
    {
        $this->assertInstanceOf(
            'Cmsable\Model\SiteTreeModelInterface',
            $this->newModel()
        );
    }

    public function testRootIdWasSetted()
    {
        $model = $this->newModel('Cmsable\Model\GenericPage', 34);

        $this->assertEquals(34, $model->getRootId());
    }

    public function testFillAdminArray()
    {
        $model = $this->newModel();
        $srcArray = $this->getCompleteSourceArray();

        $model->setSourceArray($srcArray);
        $this->assertTrue(is_array($srcArray));

    }

    protected function getCompleteSourceArray()
    {
        $adminTreeFile = __DIR__.'/../../../resources/sitetrees/admintree.php';
        return include(realpath($adminTreeFile));
    }

    protected function newModel($pageClass='Cmsable\Model\GenericPage', $rootId=2)
    {
        return new ArraySiteTreeModel($pageClass, $rootId);
    }

    public function tearDown()
    {
        m::close();
    }

}

<?php 

use Mockery as m;
use Cmsable\View\AssignedDataParser;

class AssignedDataParserTest extends BaseTest
{

    public function testImplementsInterface()
    {
        $this->assertInstanceOf(
            'Cmsable\View\TextParserInterface',
            $this->newParser()
        );
    }

    public function testReplacesFirstLevelData()
    {
        $data = [
            'salutation'    => 'Hello',
            'name'          => 'Gaylord',
            'age'           => 33,
            'taxes'         => 0.19
        ];

        $parser = $this->newParser();

        $text = '{salutation} Mr. {name} since you are {age} you pay {taxes} taxes';

        $expected = 'Hello Mr. Gaylord since you are 33 you pay 0.19 taxes';

        $this->assertEquals($expected, $parser->parse($text, $data));
    }

    public function testReplacesNestedArraysWithUmlauts()
    {
        $data = [
            'address' => [
                'street' => [
                    'name' => 'Bängnösestraße'
                ],
                'location' => 'Bangkok'
            ],
            'name' => 'Wärner'
        ];

        $parser = $this->newParser();

        $text = "Hallöö {name} schön daß Du in der {address.street.name} in {address.location} wohnst";

        $expected = "Hallöö Wärner schön daß Du in der Bängnösestraße in Bangkok wohnst";

        $this->assertEquals($expected, $parser->parse($text, $data));

    }

    public function testReplacesNestedStdClassObjects()
    {

        $data = [];

        $data['address']                  = new stdClass();
        $data['address']->street          = new stdclass();
        $data['address']->street->name    = 'Bängnösestraße';
        $data['address']->location        = 'Bangkok';
        $data['name']                     = 'Wärner';

        $parser = $this->newParser();

        $text = "Hallöö {name} schön daß Du in der {address.street.name} in {address.location} wohnst";

        $expected = "Hallöö Wärner schön daß Du in der Bängnösestraße in Bangkok wohnst";

        $this->assertEquals($expected, $parser->parse($text, $data));

    }

    public function testReplacesNestedEloquentObjects()
    {

        $data = [];

        $address = $this->newTestModel();
        $address->setAttribute('location', 'Bangkok');

        $street = $this->newTestModel();
        $street->setAttribute('name', 'Bängnösestraße');

        $address->setRelation('street', $street);

        $data['address'] = $address;

        $data['name'] = 'Wärner';

        $parser = $this->newParser();

        $text = "Hallöö {name} schön daß Du in der {address.street.name} in {address.location} wohnst";

        $expected = "Hallöö Wärner schön daß Du in der Bängnösestraße in Bangkok wohnst";

        $this->assertEquals($expected, $parser->parse($text, $data));

    }

    protected function newParser()
    {
        return new AssignedDataParser;
    }

}
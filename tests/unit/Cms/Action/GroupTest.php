<?php 

use Mockery as m;
use Cmsable\Cms\Action\Group;
use Cmsable\Cms\Action\Action;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase{

    public function testFilterDefaultReturnsCopyOfGroup(){

        $action1 = (new Action)->setName('action1')->showIn('a','b');
        $action2 = (new Action)->setName('action2')->showIn('b','c');
        $action3 = (new Action)->setName('action3')->showIn('d','f');

        $group = (new Group)->extend([$action1,$action2,$action3]);

        $newGroup = $group->filtered('default');

        $this->assertEquals($action1->name, $newGroup[0]->name);
        $this->assertEquals($action2->name, $newGroup[1]->name);
        $this->assertEquals($action3->name, $newGroup[2]->name);

    }

    public function testReturnFilterWhitelist(){

        $action1 = (new Action)->setName('action1')->showIn('a','b');
        $action2 = (new Action)->setName('action2')->showIn('b','c');
        $action3 = (new Action)->setName('action3')->showIn('d','f');

        $group = (new Group)->extend([$action1,$action2,$action3]);

        $newGroup = $group->filtered('b');

        $this->assertEquals($action1->name, $newGroup[0]->name);
        $this->assertEquals($action2->name, $newGroup[1]->name);
        $this->assertCount(2, $newGroup);

        $newGroup = $group->filtered('d');

        $this->assertEquals($action3->name, $newGroup[0]->name);
        $this->assertCount(1, $newGroup);

        $newGroup = $group->filtered('a','c');

        $this->assertEquals($action1->name, $newGroup[0]->name);
        $this->assertEquals($action2->name, $newGroup[1]->name);
        $this->assertCount(2, $newGroup);

        $newGroup = $group->filtered('a','c','d');

        $this->assertEquals($action1->name, $newGroup[0]->name);
        $this->assertEquals($action2->name, $newGroup[1]->name);
        $this->assertEquals($action3->name, $newGroup[2]->name);
        $this->assertCount(3, $newGroup);

    }

    public function testFilterBlackList(){

        $action1 = (new Action)->setName('action1')->showIn('a','b');
        $action2 = (new Action)->setName('action2')->showIn('b','c');
        $action3 = (new Action)->setName('action3')->showIn('d','f');

        $group = (new Group)->extend([$action1,$action2,$action3]);

        $newGroup = $group->filtered('!a');

        $this->assertEquals($action2->name, $newGroup[0]->name);
        $this->assertEquals($action3->name, $newGroup[1]->name);

        $newGroup = $group->filtered('!b');

        $this->assertEquals($action3->name, $newGroup[0]->name);
        $this->assertCount(1, $newGroup);

        $newGroup = $group->filtered('!f');
        $this->assertEquals($action1->name, $newGroup[0]->name);
        $this->assertEquals($action2->name, $newGroup[1]->name);
        $this->assertCount(2, $newGroup);

        $newGroup = $group->filtered('!a','!f');
        $this->assertEquals($action2->name, $newGroup[0]->name);
        $this->assertCount(1, $newGroup);

        $newGroup = $group->filtered('!a','!b');
        $this->assertEquals($action3->name, $newGroup[0]->name);
        $this->assertCount(1, $newGroup);

        $newGroup = $group->filtered('!a','!b','!f');
        $this->assertCount(0, $newGroup);

    }

    public function testFilterWhiteAndBlackList(){

        $action1 = (new Action)->setName('action1')->showIn('a','b');
        $action2 = (new Action)->setName('action2')->showIn('b','c');
        $action3 = (new Action)->setName('action3')->showIn('d','f');
        $action4 = (new Action)->setName('action3')->showIn('g','h');

        $group = (new Group)->extend([$action1, $action2, $action3, $action4]);

        $newGroup = $group->filtered('!a','g');

        $this->assertEquals($action4->name, $newGroup[0]->name);
        $this->assertCount(1, $newGroup);

        $newGroup = $group->filtered('!a','g','c');

        $this->assertEquals($action2->name, $newGroup[0]->name);
        $this->assertEquals($action4->name, $newGroup[1]->name);
        $this->assertCount(2, $newGroup);

    }

}

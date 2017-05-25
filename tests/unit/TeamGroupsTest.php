<?php
namespace Elabftw\Elabftw;

use PDO;

class TeamGroupsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->TeamGroups = new TeamGroups(1);
    }

    public function testCreate()
    {
        $this->assertTrue($this->TeamGroups->create('Group Name'));
    }
    public function testReadAll()
    {
        $this->assertTrue(is_array($this->TeamGroups->readAll()));
    }
    public function testReadName()
    {
        $this->assertTrue($this->TeamGroups->create('Group Name'));
        $all = $this->TeamGroups->readAll();
        $last = array_pop($all);
        $id = $last['id'];
        $this->assertEquals('Group Name', $this->TeamGroups->readName($id));
    }
    public function testUpdate()
    {
        $this->assertEquals('New Name', $this->TeamGroups->update('New Name', 'teamgroup_1'));
        $this->expectException(\Exception::class);
        $this->TeamGroups->update('yep', 1);
    }
    public function testUpdateMember()
    {
        $this->assertTrue($this->TeamGroups->updateMember(1, 1, 'add'));
        $this->assertTrue($this->TeamGroups->isInTeamGroup(1, 1));
        $this->assertTrue($this->TeamGroups->updateMember(1, 1, 'rm'));
        $this->assertFalse($this->TeamGroups->isInTeamGroup(1, 1));
        $this->expectException(\Exception::class);
        $this->TeamGroups->updateMember(1, 1, 'yep');
    }
    public function testDestroy()
    {
        $this->assertTrue($this->TeamGroups->destroy(1));
    }
}

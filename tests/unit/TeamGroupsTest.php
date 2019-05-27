<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Exceptions\IllegalActionException;

class TeamGroupsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Users = new Users(1);
        $this->TeamGroups = new TeamGroups($Users);
    }

    public function testCreate()
    {
        $this->TeamGroups->create('Group Name');
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->TeamGroups->readAll()));
    }

    public function testReadName()
    {
        $this->TeamGroups->create('Group Name');
        $all = $this->TeamGroups->readAll();
        $last = array_pop($all);
        $id = (int) $last['id'];
        $this->assertEquals('Group Name', $this->TeamGroups->readName($id));
    }

    public function testUpdate()
    {
        $this->assertEquals('New Name', $this->TeamGroups->update('New Name', 'teamgroup_1'));
    }

    public function testUpdateMember()
    {
        $this->TeamGroups->updateMember(1, 1, 'add');
        $this->assertTrue($this->TeamGroups->isInTeamGroup(1, 1));
        $this->TeamGroups->updateMember(1, 1, 'rm');
        $this->assertFalse($this->TeamGroups->isInTeamGroup(1, 1));
        $this->expectException(IllegalActionException::class);
        $this->TeamGroups->updateMember(1, 1, 'yep');
    }

    public function testDestroy()
    {
        $this->TeamGroups->destroy(1);
    }
}

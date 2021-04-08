<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\IllegalActionException;

class TeamGroupsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $this->TeamGroups = new TeamGroups($Users);
    }

    public function testCreate()
    {
        $this->TeamGroups->create(new ContentParams('Group Name'));
    }

    public function testRead()
    {
        $this->assertTrue(is_array($this->TeamGroups->read()));
    }

    public function testReadName()
    {
        $id = $this->TeamGroups->create(new ContentParams('Group Name'));
        $this->assertEquals('Group Name', $this->TeamGroups->readName($id));
    }

    public function testUpdate()
    {
        $this->TeamGroups->setId(1);
        $this->assertTrue($this->TeamGroups->update(new ContentParams('New Name')));
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
        $this->TeamGroups->setId(1);
        $this->TeamGroups->destroy();
    }
}

<?php declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\TeamGroupParams;
use Elabftw\Exceptions\IllegalActionException;

class TeamGroupsTest extends \PHPUnit\Framework\TestCase
{
    private TeamGroups $TeamGroups;

    protected function setUp(): void
    {
        $this->TeamGroups = new TeamGroups(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $this->TeamGroups->create(new ContentParams('Group Name'));
    }

    public function testReadOne(): void
    {
        $id = $this->TeamGroups->create(new ContentParams('Group Name'));
        $this->TeamGroups->setId($id);
        $this->assertEquals('Group Name', $this->TeamGroups->readOne()['name']);
    }

    public function testUpdate(): void
    {
        $this->TeamGroups->setId(1);
        $this->assertTrue($this->TeamGroups->update(new TeamGroupParams('New Name')));
    }

    public function testUpdateMember(): void
    {
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 1, 'group' => 1,'how' => 'add')));
        $this->assertTrue($this->TeamGroups->isInTeamGroup(1, 1));
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 1, 'group' => 1,'how' => 'rm')));
        $this->assertFalse($this->TeamGroups->isInTeamGroup(1, 1));
        $this->expectException(IllegalActionException::class);
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 1, 'group' => 1,'how' => 'yep')));
    }

    public function testRead(): void
    {
        // without users
        $this->assertIsArray($this->TeamGroups->readAll());

        // with users
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 1, 'group' => 1,'how' => 'add')));
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 2, 'group' => 1,'how' => 'add')));
        $this->assertIsArray($this->TeamGroups->readAll());
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 1, 'group' => 1,'how' => 'rm')));
        $this->TeamGroups->update(new TeamGroupParams('', 'member', array('userid' => 2, 'group' => 1,'how' => 'rm')));
    }

    public function testDestroy(): void
    {
        $this->TeamGroups->setId(1);
        $this->assertTrue($this->TeamGroups->destroy());
    }
}

<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\Action;
use Elabftw\Enums\Usergroup;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

class TeamsHelperTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private TeamsHelper $TeamsHelper;

    protected function setUp(): void
    {
        $this->TeamsHelper = new TeamsHelper(1);
    }

    public function testGetGroup(): void
    {
        $this->assertEquals(Usergroup::User, $this->TeamsHelper->getGroup());
        // now create a new team and try to get group
        $Teams = new Teams(new Users(1));
        $team = $Teams->postAction(Action::Create, array('name' => 'New team'));
        $TeamsHelper = new TeamsHelper($team);
        $this->assertEquals(Usergroup::Admin, $TeamsHelper->getGroup());
    }

    public function testIsArchivedInAllTeams(): void
    {
        $target = $this->getRandomUserInTeam(1);
        $this->assertFalse(TeamsHelper::isArchivedInAllTeams($target->userid));

        // Archive user in all teams
        $this->updateArchiveStatus($target->userid, 1);
        $this->assertTrue(TeamsHelper::isArchivedInAllTeams($target->userid));
        // Restore user archive status
        $this->updateArchiveStatus($target->userid, 0);
    }
}

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

use Elabftw\Elabftw\NullLocalPassword;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users2Teams;
use Elabftw\Models\Users\Users;

use function array_column;
use function sort;

class UsersHelperTest extends \PHPUnit\Framework\TestCase
{
    private UsersHelper $UsersHelper;

    protected function setUp(): void
    {
        $this->UsersHelper = new UsersHelper(1);
    }

    public function testCannotBeDeleted(): void
    {
        $this->assertTrue($this->UsersHelper->cannotBeDeleted());
    }

    public function testCountExperiments(): void
    {
        $this->assertIsInt($this->UsersHelper->countEntity(EntityType::Experiments));
    }

    public function testCountTimestampedExperiments(): void
    {
        $this->assertIsInt($this->UsersHelper->countTimestampedExperiments());
    }

    public function testGetTeamsFromUserid(): void
    {
        $expected = array(array('id' => 1, 'name' => 'Alpha', 'is_admin' => 1, 'is_owner' => 0, 'is_archived' => 0));
        $this->assertEquals($expected, $this->UsersHelper->getTeamsFromUserid());
    }

    public function testGetTeamsFromNotFoundUserid(): void
    {
        $UsersHelper = new UsersHelper(1337);
        $this->assertEmpty($UsersHelper->getTeamsFromUserid());
    }

    public function testGetSelectableTeams(): void
    {
        $requester = new Users(1, 1);
        $userid = $requester->createOne(
            'selectable-teams@example.com',
            array(1, 2, 3),
            new NullLocalPassword(),
            automaticValidationEnabled: true,
        );

        try {
            new Users2Teams($requester)->patchUser2Team(array(
                'team' => 2,
                'target' => 'is_archived',
                'content' => 1,
            ), $userid);

            $teams = new UsersHelper($userid)->getSelectableTeams();
            $teamIds = array_column($teams, 'id');
            sort($teamIds);

            self::assertSame(array(1, 3), $teamIds);
            self::assertSame(array(0, 0), array_column($teams, 'is_archived'));
        } finally {
            new Users($userid, 1)->destroy();
        }
    }
}

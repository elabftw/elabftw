<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;

class Users2TeamsTest extends \PHPUnit\Framework\TestCase
{
    private Users2Teams $Users2Teams;

    protected function setUp(): void
    {
        $this->Users2Teams = new Users2Teams();
    }

    public function testRmUserFromTeams(): void
    {
        $this->Users2Teams->addUserToTeams(4, array(3));
        $this->Users2Teams->rmUserFromTeams(4, array(3));
        $this->expectException(ImproperActionException::class);
        $this->Users2Teams->rmUserFromTeams(4, array(2));
    }

    public function testPatchUser2TeamGroup(): void
    {
        $params = array(
            'userid' => 2,
            'team' => 1,
            'target' => 'group',
            'content' => 4,
        );
        $this->assertEquals(4, $this->Users2Teams->patchUser2Team(new Users(1, 1), $params));
    }

    public function testPatchIsOwner(): void
    {
        $params = array(
            'userid' => 3,
            'team' => 1,
            'target' => 'is_owner',
            'content' => '1',
        );
        $this->assertEquals(1, $this->Users2Teams->patchUser2Team(new Users(1, 1), $params));
        $this->expectException(IllegalActionException::class);
        $this->Users2Teams->patchUser2Team(new Users(2, 1), $params);
    }
}

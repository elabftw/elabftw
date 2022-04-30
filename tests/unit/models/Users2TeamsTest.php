<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class Users2TeamsTest extends \PHPUnit\Framework\TestCase
{
    private Users2Teams $Users2Teams;

    protected function setUp(): void
    {
        $this->Users2Teams = new Users2Teams();
    }

    public function testRmUserFromTeams(): void
    {
        $this->Users2Teams->rmUserFromTeams(4, array(2));
        $this->Users2Teams->addUserToTeams(4, array(3));
        $this->Users2Teams->rmUserFromTeams(4, array(3));
    }
}

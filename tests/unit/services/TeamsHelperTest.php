<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Teams;
use Elabftw\Models\Users;

class TeamsHelperTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->TeamsHelper = new TeamsHelper(1);
    }

    public function testGetGroup()
    {
        $this->assertEquals(4, $this->TeamsHelper->getGroup());
        // now create a new team and try to get group
        $Teams = new Teams(new Users(1));
        $team = $Teams->create('New team');
        $TeamsHelper = new TeamsHelper($team);
        $this->assertEquals(2, $TeamsHelper->getGroup());
    }
}

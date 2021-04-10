<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class BannedUsersTest extends \PHPUnit\Framework\TestCase
{
    private BannedUsers $BannedUsers;

    protected function setUp(): void
    {
        $this->BannedUsers= new BannedUsers(new Config);
    }

    public function testCreate(): void
    {
        $fingerprint = md5('yep');
        $this->assertTrue($this->BannedUsers->create($fingerprint));
    }

    public function testReadAll(): void
    {
        $this->assertTrue(is_array($this->BannedUsers->readAll()));
    }
}

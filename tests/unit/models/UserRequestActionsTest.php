<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Users\Users;

class UserRequestActionsTest extends \PHPUnit\Framework\TestCase
{
    private UserRequestActions $ura;

    protected function setUp(): void
    {
        // use user 2 as it was used to setup the db entries in testRequestActions
        $this->ura = new UserRequestActions(new Users(2, 1));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->ura->readAll());
        $this->assertIsArray($this->ura->readAllFull());
    }

    public function testCannotReadAnotherUsersRequests(): void
    {
        $target = new Users(1, 1, new Users(2, 1));
        $this->expectException(IllegalActionException::class);
        new UserRequestActions($target)->readAll();
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/users/me/request_actions/', $this->ura->getApiPath());
    }
}

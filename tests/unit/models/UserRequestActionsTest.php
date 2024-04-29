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

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;

class UserRequestActionsTest extends \PHPUnit\Framework\TestCase
{
    private UserRequestActions $ura;

    protected function setUp(): void
    {
        $this->ura = new UserRequestActions(new Users(1, 1));
    }

    public function testRead(): void
    {
        $this->assertIsArray($this->ura->readAll());
        $this->assertIsArray($this->ura->readAllFull());
    }

    public function testReadOne(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ura->readOne();
    }

    public function testPostAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ura->postAction(Action::Create, array());
    }

    public function testPatch(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ura->patch(Action::Update, array());
    }

    public function testGetPage(): void
    {
        $this->assertEquals('api/v2/users/me/request_actions/', $this->ura->getPage());
    }

    public function testDestroy(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->ura->destroy();
    }
}

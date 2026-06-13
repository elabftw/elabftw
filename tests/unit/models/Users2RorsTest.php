<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

use function count;

class Users2RorsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $Users;

    private Users2Rors $Users2Rors;

    private string $ror;

    protected function setUp(): void
    {
        $this->ror = '02feahw73';
        $this->Users = $this->getUserInTeam(1);
        $this->Users2Rors = new Users2Rors($this->Users, $this->Users, $this->ror);
    }

    public function testGetApiPath(): void
    {
        $this->assertStringEndsWith('rors/', $this->Users2Rors->getApiPath());
    }

    public function testAll(): void
    {
        $rors = $this->Users2Rors->readAll();
        $initialCount = count($rors);
        $this->Users2Rors->postAction(Action::Create, array());
        $this->assertEquals($initialCount + 1, count($this->Users2Rors->readAll()));
        $this->assertEquals($this->ror, $this->Users2Rors->readOne()['ror']);
        $this->Users2Rors->destroy();
        $this->assertEquals($initialCount, count($this->Users2Rors->readAll()));
    }

    public function testNotAdmin(): void
    {
        $Users2Rors = new Users2Rors($this->getUserInTeam(2), $this->getUserInTeam(3), $this->ror);
        $this->expectException(IllegalActionException::class);
        $Users2Rors->postAction(Action::Create, array());
    }

    public function testInvalidCreate(): void
    {
        $Users2Rors = new Users2Rors($this->Users, $this->Users);
        $this->expectException(ImproperActionException::class);
        $Users2Rors->postAction(Action::Create, array());
    }

    public function testInvalidRor(): void
    {
        $this->expectException(ImproperActionException::class);
        new Users2Rors($this->Users, $this->Users, 'not a ror');
    }
}

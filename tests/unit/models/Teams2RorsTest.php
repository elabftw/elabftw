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

use function count;

class Teams2RorsTest extends \PHPUnit\Framework\TestCase
{
    private Teams2Rors $Teams2Rors;

    private string $ror;

    protected function setUp(): void
    {
        $this->ror = '04vfs2w97';
        $teamId = 3;
        $this->Teams2Rors = new Teams2Rors($teamId, true, $this->ror);
    }

    public function testGetApiPath(): void
    {
        $this->assertStringEndsWith('rors/', $this->Teams2Rors->getApiPath());
    }

    public function testAll(): void
    {
        $rors = $this->Teams2Rors->readAll();
        $initialCount = count($rors);
        $this->Teams2Rors->postAction(Action::Create, array());
        $this->assertEquals($initialCount + 1, count($this->Teams2Rors->readAll()));
        $this->assertEquals($this->ror, $this->Teams2Rors->readOne()['ror']);
        $this->Teams2Rors->destroy();
        $this->assertEquals($initialCount, count($this->Teams2Rors->readAll()));
    }

    public function testNotAdmin(): void
    {
        $teamId = 3;
        $Teams2Rors = new Teams2Rors($teamId, false, $this->ror);
        $this->expectException(IllegalActionException::class);
        $Teams2Rors->postAction(Action::Create, array());
    }

    public function testInvalidCreate(): void
    {
        $Teams2Rors = new Teams2Rors(3, true);
        $this->expectException(ImproperActionException::class);
        $Teams2Rors->postAction(Action::Create, array());
    }

    public function testInvalidRor(): void
    {
        $this->expectException(ImproperActionException::class);
        new Teams2Rors(3, true, 'not a ror');
    }
}

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
use Elabftw\Traits\TestsUtilsTrait;

use function count;

class Instance2RorsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Instance2Rors $Instance2Rors;

    private string $ror;

    protected function setUp(): void
    {
        $this->ror = '05kxdq627';
        $this->Instance2Rors = new Instance2Rors(true, $this->ror);
    }

    public function testGetApiPath(): void
    {
        $this->assertStringEndsWith('rors/', $this->Instance2Rors->getApiPath());
    }

    public function testAll(): void
    {
        $rors = $this->Instance2Rors->readAll();
        $initialCount = count($rors);
        $this->Instance2Rors->postAction(Action::Create, array());
        $this->assertEquals($initialCount + 1, count($this->Instance2Rors->readAll()));
        $this->assertEquals($this->ror, $this->Instance2Rors->readOne()['ror']);
        $this->Instance2Rors->destroy();
        $this->assertEquals($initialCount, count($this->Instance2Rors->readAll()));
    }

    public function testNotSysadmin(): void
    {
        $Instance2Rors = new Instance2Rors(false, $this->ror);
        $this->expectException(IllegalActionException::class);
        $Instance2Rors->postAction(Action::Create, array());
    }

    public function testInvalidCreate(): void
    {
        $Instance2Rors = new Instance2Rors(true);
        $this->expectException(ImproperActionException::class);
        $Instance2Rors->postAction(Action::Create, array());
    }

    public function testInvalidRor(): void
    {
        $this->expectException(ImproperActionException::class);
        new Instance2Rors(true, 'not a ror');
    }
}

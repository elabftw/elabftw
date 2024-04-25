<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Services\MpdfQrProvider;

class MakeQrPngTest extends \PHPUnit\Framework\TestCase
{
    private MakeQrPng $Maker;

    protected function setUp(): void
    {
        $Entity = new Experiments(new Users(1, 1), 1);
        $this->Maker = new MakeQrPng(new MpdfQrProvider(), $Entity, 1, 250);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->Maker->getFileContent());
    }

    public function testGetFileContentSmallsize(): void
    {
        $Entity = new Experiments(new Users(1, 1), 1);

        $Maker = new MakeQrPng(new MpdfQrProvider(), $Entity, 1, 4);
        $this->assertIsString($Maker->getFileContent());
    }

    public function testGetFileName(): void
    {
        $this->assertStringEndsWith('qr-code.elabftw.png', $this->Maker->getFileName());
    }
}

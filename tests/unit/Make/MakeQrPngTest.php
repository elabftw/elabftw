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
use Elabftw\Services\MpdfQrProvider;
use Elabftw\Traits\TestsUtilsTrait;

class MakeQrPngTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeQrPng $Maker;

    private Experiments $Entity;

    protected function setUp(): void
    {
        $this->Entity = $this->getFreshExperiment();
        $this->Maker = new MakeQrPng(new MpdfQrProvider(), $this->Entity, 250);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->Maker->getFileContent());
    }

    public function testGetFileContentSmallsize(): void
    {
        $Maker = new MakeQrPng(new MpdfQrProvider(), $this->Entity, 250);
        $this->assertIsString($Maker->getFileContent());
    }

    public function testGetFileContentNotitle(): void
    {
        $Maker = new MakeQrPng(new MpdfQrProvider(), $this->Entity, 250, false);
        $this->assertIsString($Maker->getFileContent());
    }

    public function testGetFileName(): void
    {
        $this->assertStringEndsWith('qr-code.elabftw.png', $this->Maker->getFileName());
    }
}

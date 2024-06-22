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

use Elabftw\Elabftw\EntitySlug;
use Elabftw\Enums\EntityType;
use Elabftw\Models\Users;
use Elabftw\Services\MpdfQrProvider;

class MakeQrPngTest extends \PHPUnit\Framework\TestCase
{
    private MakeQrPng $Maker;

    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
        $this->Maker = new MakeQrPng(new MpdfQrProvider(), $this->Users, array(new EntitySlug(EntityType::Experiments, 1)), 250);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->Maker->getFileContent());
    }

    public function testGetFileContentSmallsize(): void
    {
        $Maker = new MakeQrPng(new MpdfQrProvider(), $this->Users, array(new EntitySlug(EntityType::Experiments, 1)), 250);
        $this->assertIsString($Maker->getFileContent());
    }

    public function testGetFileName(): void
    {
        $this->assertStringEndsWith('qr-code.elabftw.png', $this->Maker->getFileName());
    }
}

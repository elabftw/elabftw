<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use Elabftw\Services\MpdfProvider;

class MakeQrPdfTest extends \PHPUnit\Framework\TestCase
{
    private MakeQrPdf $MakePdf;

    protected function setUp(): void
    {
        $requester = new Users(1, 1);
        $MpdfProvider = new MpdfProvider('Toto');
        $this->MakePdf = new MakeQrPdf($MpdfProvider, $requester, array(new Experiments($requester, 1), new Experiments($requester, 2)));
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('qr-codes.elabftw.pdf', $this->MakePdf->getFileName());
    }
}

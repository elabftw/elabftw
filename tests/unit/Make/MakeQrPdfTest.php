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

use Elabftw\Services\MpdfProvider;
use Elabftw\Traits\TestsUtilsTrait;

class MakeQrPdfTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeQrPdf $MakePdf;

    protected function setUp(): void
    {
        $requester = $this->getRandomUserInTeam(1);
        $MpdfProvider = new MpdfProvider($requester->userData['firstname']);
        $this->MakePdf = new MakeQrPdf(
            $MpdfProvider,
            $requester,
            array($this->getFreshExperimentWithGivenUser($requester), $this->getFreshExperimentWithGivenUser($requester)),
        );
    }

    public function testGenerate(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
        $this->assertEquals('qr-codes.elabftw.pdf', $this->MakePdf->getFileName());
    }
}

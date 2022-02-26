<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\CreateUpload;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class MakeMultiPdfTest extends \PHPUnit\Framework\TestCase
{
    private MakeMultiPdf $MakePdf;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $Entity = new Experiments(new Users(1, 1), 1);
        // test >Append attached PDFs<
        $Entity->Users->userData['append_pdfs'] = true;
        // add a pdf
        $Entity->Uploads->create(new CreateUpload('digicert.pdf', dirname(__DIR__, 2) . '/_data/digicert.pdf'));
        // add a pdf with password -> cannot be appended
        $Entity->Uploads->create(new CreateUpload('with_password_123456.pdf', dirname(__DIR__, 2) . '/_data/with_password_123456.pdf'));
        $MpdfProvider = new MpdfProvider('Toto');
        $this->MakePdf = new MakeMultiPdf($MpdfProvider, $Entity, $idArr);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('multientries.elabftw.pdf', $this->MakePdf->getFileName());
    }
}

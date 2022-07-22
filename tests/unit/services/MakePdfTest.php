<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;

class MakePdfTest extends \PHPUnit\Framework\TestCase
{
    private MakePdf $MakePdf;

    protected function setUp(): void
    {
        $Entity = new Experiments(new Users(1, 1), 1);
        $Entity->canOrExplode('read');
        // add invalid tex macro to body to cover notification being created upon failing mathjax
        $Entity->entityData['body'] .= '\n<p>$ \someInvalidTexMacro $</p>';
        // test >Append attached PDFs<
        $Entity->Users->userData['append_pdfs'] = true;
        // add a pdf
        $Entity->Uploads->create(new CreateUpload('digicert.pdf', dirname(__DIR__, 2) . '/_data/digicert.pdf'));
        // add a pdf with password -> cannot be appended
        $Entity->Uploads->create(new CreateUpload('with_password_123456.pdf', dirname(__DIR__, 2) . '/_data/with_password_123456.pdf'));
        // add an image to the body
        $id = $Entity->Uploads->create(new CreateUpload('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Entity->Uploads->setId($id);
        $upArr = $Entity->Uploads->read(new ContentParams());
        $Entity->entityData['body'] .= '\n<p><img src="app/download.php?f=' . $upArr['long_name'] . '&amp;storage=' . $upArr['storage'] . '"></p>';
        // without storage part of the query to test getStorageFromLongname
        $Entity->entityData['body'] .= '\n<p><img src="app/download.php?f=' . $upArr['long_name'] . '"></p>';
        // test upper case file extension
        $id = $Entity->Uploads->create(new CreateUpload('example.PNG', dirname(__DIR__, 2) . '/_data/example.png'));
        $Entity->Uploads->setId($id);
        $upArr = $Entity->Uploads->read(new ContentParams());
        $Entity->entityData['body'] .= '\n<p><img src="app/download.php?f=' . $upArr['long_name'] . '"></p>';
        $MpdfProvider = new MpdfProvider('Toto');
        $this->MakePdf = new MakePdf($MpdfProvider, $Entity);
    }

    public function testGetFileContent(): void
    {
        $this->assertIsString($this->MakePdf->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('application/pdf', $this->MakePdf->getContentType());
    }
}

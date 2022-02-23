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
        $Entity->Uploads->create($this->createUpload('digicert.pdf'));
        // add a pdf with password -> cannot be appended
        $Entity->Uploads->create($this->createUpload('with_password_123456.pdf'));
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

    public function createUpload(string $fileName): CreateUpload
    {
        $params = $this->createMock(CreateUpload::class);
        // this would be the real name of the file uploaded by user
        $params->method('getFilename')->willReturn($fileName);
        // and this corresponds to the temporary file created after upload
        $tmpFilePath = '/tmp/phpELABFTW';
        $params->method('getFilePath')->willReturn($tmpFilePath);
        $fs = (new StorageFactory(StorageFactory::MEMORY))->getStorage()->getFs();
        // write our temporary file as if it was uploaded by a user
        $fs->createDirectory('tmp');
        $fixturesFs = (new StorageFactory(StorageFactory::FIXTURES))->getStorage()->getFs();
        $fs->write(basename($tmpFilePath), $fixturesFs->read($fileName));
        // we use the same fs for source and storage because it's all in memory anyway
        $params->method('getSourceFs')->willReturn($fs);
        return $params;
    }
}

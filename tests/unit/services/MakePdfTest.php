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
use Elabftw\Models\Uploads;
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
        $Uploads = new Uploads($Entity);
        // add a pdf
        $Uploads->create($this->createUpload('digicert.pdf'));
        // add a pdf with password -> cannot be appended
        $Uploads->create($this->createUpload('with_password_123456.pdf'));
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

<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Services\StorageFactory;

class UploadsTest extends \PHPUnit\Framework\TestCase
{
    private Items $Entity;

    protected function setUp(): void
    {
        $this->Entity = new Items(new Users(1, 1), 10);
    }

    public function testCreate(): void
    {
        $params = $this->createMock(CreateUpload::class);
        // this would be the real name of the file uploaded by user
        $params->method('getFilename')->willReturn('example.png');
        // and this corresponds to the temporary file created after upload
        $tmpFilePath = '/tmp/phpELABFTW';
        $params->method('getFilePath')->willReturn($tmpFilePath);
        $fs = (new StorageFactory(StorageFactory::MEMORY))->getStorage()->getFs();
        // write our temporary file as if it was uploaded by a user
        $fs->createDirectory('tmp');
        // a txt file was failing the mime type, so use a png
        $fixturesFs = (new StorageFactory(StorageFactory::FIXTURES))->getStorage()->getFs();
        $fs->write(basename($tmpFilePath), $fixturesFs->read('example.png'));
        // we use the same fs for source and storage because it's all in memory anyway
        $params->method('getSourceFs')->willReturn($fs);

        $Uploads = new Uploads($this->Entity);
        $Uploads->create($params);
    }

    // same as above, but this file will fail mime type detection
    public function testCreateMimeFail(): void
    {
        $params = $this->createMock(CreateUpload::class);
        // this would be the real name of the file uploaded by user
        $params->method('getFilename')->willReturn('example.txt');
        // and this corresponds to the temporary file created after upload
        $tmpFilePath = '/tmp/phpELABFTW';
        $params->method('getFilePath')->willReturn($tmpFilePath);
        $fs = (new StorageFactory(StorageFactory::MEMORY))->getStorage()->getFs();
        // write our temporary file as if it was uploaded by a user
        $fs->createDirectory('tmp');
        $fs->write(basename($tmpFilePath), 'blah');
        // we use the same fs for source and storage because it's all in memory anyway
        $params->method('getSourceFs')->willReturn($fs);

        $Uploads = new Uploads($this->Entity);
        $Uploads->create($params);
    }

    public function testGetStorageFromLongname(): void
    {
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUpload('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Uploads->setId($id);
        $upArr = $Uploads->read(new ContentParams());
        $this->assertEquals($upArr['storage'], $Uploads->getStorageFromLongname($upArr['long_name']));
    }
}

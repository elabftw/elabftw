<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateImmutableUpload;
use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\UploadParams;
use Elabftw\Enums\FileFromString;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Factories\StorageFactory;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadsTest extends \PHPUnit\Framework\TestCase
{
    private Items $Entity;

    protected function setUp(): void
    {
        $this->Entity = new Items(new Users(1, 1), 11);
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

    public function testCreatePngFromString(): void
    {
        $dataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAABhWlDQ1BJQ0MgcHJvZmlsZQAAKJF9kT1Iw0AcxV9TpSIVByuIKGSoThZERcRJq1CECqFWaNXB5NIPoUlDkuLiKLgWHPxYrDq4OOvq4CoIgh8gjk5Oii5S4v+SQosYD4778e7e4+4dINRKTLPaRgFNt81UIi5msiti6BUhDKIX0wjJzDJmJSkJ3/F1jwBf72I8y//cn6NLzVkMCIjEM8wwbeJ14slN2+C8TxxhRVklPiceMemCxI9cVzx+41xwWeCZETOdmiOOEIuFFlZamBVNjXiCOKpqOuULGY9VzluctVKFNe7JXxjO6ctLXKc5gAQWsAgJIhRUsIESbMRo1UmxkKL9uI+/3/VL5FLItQFGjnmUoUF2/eB/8LtbKz8+5iWF40D7i+N8DAGhXaBedZzvY8epnwDBZ+BKb/rLNWDqk/RqU4seAd3bwMV1U1P2gMsdoO/JkE3ZlYI0hXweeD+jb8oCPbdA56rXW2Mfpw9AmrpK3gAHh8BwgbLXfN7d0drbv2ca/f0AoG1yuTjmrdUAAAAGYktHRAD/AP8A/6C9p5MAAAAJcEhZcwAALiMAAC4jAXilP3YAAAAHdElNRQfmCAEBGRl6rBV0AAAAD3RFWHRDb21tZW50AGVMYWJGVFfEIDydAAAAG0lEQVQI12NkYGD4X1tby8Cwf//+/8+ePfsPAD1lCWVCgcPRAAAAAElFTkSuQmCC';
        $id = $this->Entity->Uploads->createFromString(
            FileFromString::Png,
            'some.png',
            $dataUrl,
        );
        $this->assertIsInt($id);
    }

    public function testCreatePngFromInvalidString(): void
    {
        $dataUrl = 'data:';
        $this->expectException(RuntimeException::class);
        $this->Entity->Uploads->createFromString(
            FileFromString::Png,
            'invalid.png',
            $dataUrl,
        );
    }

    public function testUploadingPhpFile(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Entity->Uploads->create(new CreateUpload('some.php', __FILE__));
    }

    public function testEditAnImmutableFile(): void
    {
        $id = $this->Entity->Uploads->create(new CreateImmutableUpload('some-immutable.zip', dirname(__DIR__, 2) . '/_data/importable.zip'));
        $this->Entity->Uploads->setId($id);
        $this->expectException(IllegalActionException::class);
        $this->Entity->Uploads->update(new UploadParams('new', 'real_name'));
    }

    public function testGetStorageFromLongname(): void
    {
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUpload('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Uploads->setId($id);
        $this->assertEquals($Uploads->uploadData['storage'], $Uploads->getStorageFromLongname($Uploads->uploadData['long_name']));
    }

    public function testGetIdFromLongname(): void
    {
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUpload('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Uploads->setId($id);
        $this->assertEquals($Uploads->uploadData['id'], $Uploads->getIdFromLongname($Uploads->uploadData['long_name']));
    }

    public function testReplace(): void
    {
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUpload('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Uploads->setId($id);
        $upArrBefore = $Uploads->uploadData;

        $upArrNew = $Uploads->replace(new UploadParams('', 'file', new UploadedFile(dirname(__DIR__, 2) . '/_data/example.png', 'example.png')));
        $this->assertIsArray($upArrNew);
        $this->assertEquals($upArrBefore['comment'], $upArrNew['comment']);

        $Uploads->setId($id);
        $upArrAfter = $Uploads->uploadData;

        // access the updated entry in the nested array
        $this->assertEquals((int) $upArrAfter['state'], $Uploads::STATE_ARCHIVED);
    }

    public function testInvalidId(): void
    {
        $this->expectException(IllegalActionException::class);
        $this->Entity->Uploads->setId(0);
    }

    public function testDestroyAll(): void
    {
        $this->Entity->Uploads->destroyAll();
    }
}

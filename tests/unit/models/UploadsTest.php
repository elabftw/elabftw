<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Enums\Action;
use Elabftw\Enums\FileFromString;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Storage\Tmp;
use Elabftw\Traits\TestsUtilsTrait;
use RuntimeException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Response;

class UploadsTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Items $Entity;

    protected function setUp(): void
    {
        $this->Entity = $this->getFreshItem();
    }

    public function testCreate(): void
    {
        $fixturesFs = Storage::FIXTURES->getStorage();
        $fileName = 'example.png';
        $this->Entity->Uploads->create(new CreateUploadFromLocalFile($fileName, $fixturesFs->getPath() . '/' . $fileName));
        $this->Entity->Uploads->duplicate($this->Entity);
    }

    public function testCreateEmptyRealname(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Entity->Uploads->postAction(Action::Create, array());
    }

    public function testReadFilesizeSum(): void
    {
        $this->assertIsInt($this->Entity->Uploads->readFilesizeSum());
    }

    // same as above, but this file will fail mime type detection
    public function testCreateMimeFail(): void
    {
        $fixturesFs = Storage::FIXTURES->getStorage();
        $fileName = 'example.txt';
        $this->Entity->Uploads->create(new CreateUploadFromLocalFile('example.pdf', $fixturesFs->getPath() . '/' . $fileName));
    }

    public function testCreatePngFromString(): void
    {
        $dataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAABhWlDQ1BJQ0MgcHJvZmlsZQAAKJF9kT1Iw0AcxV9TpSIVByuIKGSoThZERcRJq1CECqFWaNXB5NIPoUlDkuLiKLgWHPxYrDq4OOvq4CoIgh8gjk5Oii5S4v+SQosYD4778e7e4+4dINRKTLPaRgFNt81UIi5msiti6BUhDKIX0wjJzDJmJSkJ3/F1jwBf72I8y//cn6NLzVkMCIjEM8wwbeJ14slN2+C8TxxhRVklPiceMemCxI9cVzx+41xwWeCZETOdmiOOEIuFFlZamBVNjXiCOKpqOuULGY9VzluctVKFNe7JXxjO6ctLXKc5gAQWsAgJIhRUsIESbMRo1UmxkKL9uI+/3/VL5FLItQFGjnmUoUF2/eB/8LtbKz8+5iWF40D7i+N8DAGhXaBedZzvY8epnwDBZ+BKb/rLNWDqk/RqU4seAd3bwMV1U1P2gMsdoO/JkE3ZlYI0hXweeD+jb8oCPbdA56rXW2Mfpw9AmrpK3gAHh8BwgbLXfN7d0drbv2ca/f0AoG1yuTjmrdUAAAAGYktHRAD/AP8A/6C9p5MAAAAJcEhZcwAALiMAAC4jAXilP3YAAAAHdElNRQfmCAEBGRl6rBV0AAAAD3RFWHRDb21tZW50AGVMYWJGVFfEIDydAAAAG0lEQVQI12NkYGD4X1tby8Cwf//+/8+ePfsPAD1lCWVCgcPRAAAAAElFTkSuQmCC';
        $id = $this->Entity->Uploads->postAction(Action::CreateFromString, array(
            'file_type' => FileFromString::Png->value,
            'real_name' => 'some.png',
            'content' => $dataUrl,
        ));
        $this->assertIsInt($id);
    }

    public function testCreatePngFromInvalidString(): void
    {
        $dataUrl = 'data:';
        $this->expectException(RuntimeException::class);
        $this->Entity->Uploads->postAction(Action::CreateFromString, array(
            'file_type' => FileFromString::Png->value,
            'real_name' => 'invalid.png',
            'content' => $dataUrl,
        ));
    }

    public function testCreateFromStringNoExtension(): void
    {
        $id = $this->Entity->Uploads->postAction(Action::CreateFromString, array(
            'file_type' => FileFromString::Mol->value,
            'real_name' => 'no_extension',
            'content' => 'molfilecontent',
        ));
        $this->Entity->Uploads->setId($id);
        $this->assertEquals('no_extension.mol', $this->Entity->Uploads->uploadData['real_name']);
    }

    public function testDuplicate(): void
    {
        $source = new Experiments(new Users(1, 1));
        $source->setId($source->create());
        $source->Uploads->createFromString(FileFromString::Json, 'test.json', '{}');

        $target = new Experiments(new Users(1, 1));
        $target->setId($target->create());
        $source->Uploads->duplicate($target);

        $targetArr = $target->Uploads->readAll();
        $this->assertEquals('test.json', $targetArr[0]['real_name']);
    }

    public function testUploadingPhpFile(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some.php', __FILE__));
    }

    public function testReadBinary(): void
    {
        $id = $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some-file.zip', dirname(__DIR__, 2) . '/_data/importable.zip'));
        $this->Entity->Uploads->setId($id);
        $this->assertInstanceOf(Response::class, $this->Entity->Uploads->readBinary());
    }

    public function testPatch(): void
    {
        $id = $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some-file.zip', dirname(__DIR__, 2) . '/_data/importable.zip'));
        $this->Entity->Uploads->setId($id);
        $this->Entity->Uploads->patch(Action::Archive, array());
        $this->Entity->Uploads->patch(Action::Update, array(
            'real_name' => 'new real name',
            'comment' => 'new file comment',
            'state' => (string) State::Deleted->value,
        ));
    }

    public function testGetApiPath(): void
    {
        $this->assertIsString($this->Entity->Uploads->getApiPath());
    }

    public function testEditAnImmutableFile(): void
    {
        $id = $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some-immutable.zip', dirname(__DIR__, 2) . '/_data/importable.zip', immutable: 1));
        $this->Entity->Uploads->setId($id);
        $this->expectException(IllegalActionException::class);
        $this->Entity->Uploads->patch(Action::Update, array('real_name' => 'new'));
    }

    public function testGetStorageFromLongname(): void
    {
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Uploads->setId($id);
        $this->assertEquals($Uploads->uploadData['storage'], $Uploads->getStorageFromLongname($Uploads->uploadData['long_name']));
    }

    public function testReplaceFromPost(): void
    {
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png'));
        $Uploads->setId($id);
        $fs = new Tmp()->getFs();
        $fs->write('a.file', 'yes');
        $this->assertIsInt($Uploads->postAction(Action::Replace, array('real_name' => 'yep', 'filePath' => '/tmp/a.file')));
    }

    public function testReplace(): void
    {
        $comment = 'some super duper comment';
        $Uploads = new Uploads($this->Entity);
        $id = $Uploads->create(new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png', $comment));
        $Uploads->setId($id);
        $upArrBefore = $Uploads->uploadData;

        $id = $Uploads->replace(new CreateUploadFromLocalFile('example.png', dirname(__DIR__, 2) . '/_data/example.png', $comment));
        $this->assertIsInt($id);
        // make sure the old one is archived
        $this->assertEquals($Uploads->readOne()['state'], State::Archived->value);
        $Uploads->setId($id);
        // make sure the comment is the same
        $this->assertEquals($upArrBefore['comment'], $Uploads->uploadData['comment']);
    }

    public function testInvalidId(): void
    {
        $this->expectException(IllegalActionException::class);
        $this->Entity->Uploads->setId(0);
    }

    public function testReadAll(): void
    {
        // create 2 uploads
        $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some-file.zip', dirname(__DIR__, 2) . '/_data/importable.zip'));
        $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some-file.zip', dirname(__DIR__, 2) . '/_data/importable.zip'));
        // create an archived upload
        $id = $this->Entity->Uploads->create(new CreateUploadFromLocalFile('some-file.zip', dirname(__DIR__, 2) . '/_data/importable.zip'));
        $this->Entity->Uploads->setId($id);
        $this->Entity->Uploads->patch(Action::Archive, array());
        // display only archived uploads
        $q = $this->Entity->Uploads->getQueryParams(new InputBag(array('state' => '2')));
        $archivedUploads = $this->Entity->Uploads->readAll($q);
        $this->assertIsArray($archivedUploads);
        $this->assertNotEmpty($archivedUploads);
        foreach ($archivedUploads as $upload) {
            $this->assertEquals(State::Archived->value, $upload['state'], 'Upload state should be archived (2)');
        }
        $this->assertGreaterThanOrEqual(
            count($archivedUploads),
            count($this->Entity->Uploads->readAll()),
            'All uploads should be more than or equal to archived (but not fewer).'
        );
    }

    public function testDestroyAll(): void
    {
        $this->assertTrue($this->Entity->Uploads->destroyAll());
    }
}

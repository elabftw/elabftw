<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Services\StorageFactory;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\UnableToReadFile;

class SqlTest extends \PHPUnit\Framework\TestCase
{
    private Sql $Sql;

    protected function setUp(): void
    {
        $this->Sql = new Sql((new StorageFactory(StorageFactory::FIXTURES))->getStorage()->getFs());
    }

    public function testExecFile(): void
    {
        $this->Sql->execFile('dummy.sql');
    }

    public function testExecNonExistingFile(): void
    {
        $this->expectException(UnableToReadFile::class);
        $this->Sql->execFile('purple.sql');
    }

    public function testBrokenFilesystem(): void
    {
        $fsMock = $this->createMock(Fs::class);
        $fsMock->method('read')->will($this->throwException(new UnableToReadFile()));
        $Sql = new Sql($fsMock);
        $this->expectException(UnableToReadFile::class);
        $Sql->execFile('osef');
    }
}

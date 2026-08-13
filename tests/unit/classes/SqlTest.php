<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Storage;
use Elabftw\Exceptions\DatabaseErrorException;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\UnableToReadFile;
use Symfony\Component\Console\Output\NullOutput;

class SqlTest extends \PHPUnit\Framework\TestCase
{
    private Sql $Sql;

    protected function setUp(): void
    {
        $this->Sql = new Sql(Storage::FIXTURES->getStorage()->getFs(), new NullOutput());
    }

    public function testExecFile(): void
    {
        $this->assertEquals(3, $this->Sql->execFile('dummy.sql'));
    }

    public function testExecBrokenFile(): void
    {
        // exec with force option
        $this->assertEquals(3, $this->Sql->execFile('dummy-broken.sql', true));
        // no force this time, so we can expect an error
        $this->expectException(DatabaseErrorException::class);
        $this->assertEquals(3, $this->Sql->execFile('dummy-broken.sql'));
    }

    public function testExecFileWithRollback(): void
    {
        $fsMock = $this->createMock(Fs::class);
        $fsMock->method('fileExists')->willReturn(false);
        $fsMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnMap(array(
                array('broken.sql', 'SELECT * FROM users;' . PHP_EOL . 'SELECT not_existing FROM not_existing_either;'),
                array('rollback.sql', 'SELECT * FROM teams;'),
            ));
        $Sql = new Sql($fsMock, new NullOutput());

        $this->expectException(DatabaseErrorException::class);
        $Sql->execFileWithRollback('broken.sql', 'rollback.sql');
    }

    public function testExecFileWithRollbackPreservesOriginalExceptionWhenRollbackFails(): void
    {
        $fsMock = $this->createMock(Fs::class);
        $fsMock->method('fileExists')->willReturn(false);
        $fsMock->expects($this->exactly(2))
            ->method('read')
            ->willReturnCallback(static function (string $filename): string {
                if ($filename === 'broken.sql') {
                    return 'SELECT not_existing FROM not_existing_either;';
                }
                throw new UnableToReadFile();
            });
        $Sql = new Sql($fsMock, new NullOutput());

        $this->expectException(DatabaseErrorException::class);
        $Sql->execFileWithRollback('broken.sql', 'rollback.sql');
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
        $Sql = new Sql($fsMock, new NullOutput());
        $this->expectException(UnableToReadFile::class);
        $Sql->execFile('osef');
    }
}

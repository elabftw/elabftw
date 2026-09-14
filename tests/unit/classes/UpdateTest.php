<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Component\Console\Output\NullOutput;

use function sprintf;
use function dirname;
use function str_replace;

class UpdateTest extends \PHPUnit\Framework\TestCase
{
    private Fs $Fs;

    private Sql $Sql;

    public function setUp(): void
    {
        $this->Fs = new Fs(new InMemoryFilesystemAdapter());
        $this->Sql = new Sql($this->Fs, new NullOutput());
        $this->Fs->write('migrations.sql', FsTools::getFs(dirname(__DIR__, 3) . '/src/sql')->read('migrations.sql'));
        $this->Sql->execFile('migrations.sql');
        Db::getConnection()->q(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $this->Fs->read('migrations.sql')));
    }

    protected function tearDown(): void
    {
        Db::getConnection()->q('DROP TEMPORARY TABLE IF EXISTS schema_migrations');
    }

    public function testCheckSchema(): void
    {
        $checker = new SchemaVersionChecker(SchemaVersionChecker::REQUIRED_SCHEMA - 1);
        $this->expectException(InvalidSchemaException::class);
        $checker->checkSchema();
    }

    public function testRunUpdateScript(): void
    {
        // create a fake schema file
        $this->Fs->write(sprintf('schema%d.sql', SchemaVersionChecker::REQUIRED_SCHEMA), 'SELECT 1;');
        $id = '2026_09_14_090000_after_legacy';
        $this->Fs->write('migrations/' . $id . '.sql', 'SELECT 1;');
        $this->Fs->write('migrations/' . $id . '-down.sql', 'SELECT 1;');
        $Update = new Update(SchemaVersionChecker::REQUIRED_SCHEMA - 1, $this->Sql);
        $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, $Update->runUpdateScript());
        self::assertSame(array($id => 1), (new Migrations($this->Fs, new NullOutput()))->applied());
    }

    public function testOldAfInstance(): void
    {
        $Update = new Update(36, $this->Sql);
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }

    public function testVersion2(): void
    {
        $Update = new Update(40, $this->Sql);
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }
}

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

use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Component\Console\Output\NullOutput;

use function sprintf;

class UpdateTest extends \PHPUnit\Framework\TestCase
{
    private Fs $Fs;

    private Sql $Sql;

    public function setUp(): void
    {
        $this->Fs = new Fs(new InMemoryFilesystemAdapter());
        $this->Sql = new Sql($this->Fs, new NullOutput());
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
        $this->Fs->write(sprintf('schema%d.sql', SchemaVersionChecker::REQUIRED_SCHEMA), 'SELECT 1');
        $Update = new Update(SchemaVersionChecker::REQUIRED_SCHEMA - 1, $this->Sql, new Migrations($this->Fs));
        $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, $Update->runUpdateScript());
    }

    public function testForcedMigrationErrorIsNotRecorded(): void
    {
        $uuid = Migrations::generateUuidV7();
        $this->Fs->write(sprintf('migration-%s.sql', $uuid), 'SELECT not_existing FROM not_existing_either;');
        $this->Fs->write(sprintf('migration-%s.down.sql', $uuid), 'SELECT 1;');
        $Migrations = new Migrations($this->Fs);
        $Update = new Update(SchemaVersionChecker::REQUIRED_SCHEMA, $this->Sql, $Migrations);

        try {
            $Update->runUpdateScript(true);
            $this->fail('Expected the migration to fail.');
        } catch (DatabaseErrorException) {
            $this->assertFalse($Migrations->isApplied($uuid));
        }
    }

    public function testOldAfInstance(): void
    {
        $Update = new Update(36, $this->Sql, new Migrations($this->Fs));
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }

    public function testVersion2(): void
    {
        $Update = new Update(40, $this->Sql, new Migrations($this->Fs));
        $this->expectException(ImproperActionException::class);
        $Update->runUpdateScript();
    }
}

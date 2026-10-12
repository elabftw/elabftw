<?php

declare(strict_types=1);

namespace Elabftw\Elabftw;

/**
 * @author Nicolas CARPi <Deltablot>
 * @author Mouss <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

use Elabftw\Exceptions\DatabaseErrorException;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

use function sort;
use function sprintf;

class UpdateMigrationsTest extends TestCase
{
    public function testPendingMigrationRunsOnceAndIsRecorded(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $uuid = Migrations::generateUuidV7();
        $fs->write(sprintf('migration-%s_create-test.sql', $uuid), 'SELECT 1;');
        $fs->write(sprintf('migration-%s_create-test.down.sql', $uuid), 'SELECT 1;');
        $migrations = new Migrations($fs);
        $update = new Update(SchemaVersionChecker::REQUIRED_SCHEMA, new Sql($fs, new NullOutput()), $migrations);

        try {
            $this->assertSame(1, $migrations->getPendingCount());
            $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, $update->runUpdateScript());
            $this->assertTrue($migrations->isApplied($uuid));
            $this->assertSame(0, $migrations->getPendingCount());

            // Running db:update again must not re-apply an already recorded migration.
            $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, $update->runUpdateScript());
            $this->assertSame(0, $migrations->getPendingCount());
        } finally {
            $migrations->removeApplied($uuid);
        }
    }

    public function testFailedMigrationLeavesPreviousMigrationRecorded(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $ids = array(Migrations::generateUuidV7(), Migrations::generateUuidV7());
        sort($ids, SORT_STRING);
        [$first, $second] = $ids;
        $fs->write(sprintf('migration-%s.sql', $first), 'SELECT 1;');
        $fs->write(sprintf('migration-%s.down.sql', $first), 'SELECT 1;');
        $fs->write(sprintf('migration-%s.sql', $second), 'SELECT not_existing FROM not_existing_either;');
        $fs->write(sprintf('migration-%s.down.sql', $second), 'SELECT 1;');
        $migrations = new Migrations($fs);
        $update = new Update(SchemaVersionChecker::REQUIRED_SCHEMA, new Sql($fs, new NullOutput()), $migrations);

        try {
            // UUID migrations must still fail under --force: partially applied DDL is unsafe.
            $this->expectException(DatabaseErrorException::class);
            $update->runUpdateScript(true);
        } finally {
            $this->assertTrue($migrations->isApplied($first));
            $this->assertFalse($migrations->isApplied($second));
            $migrations->removeApplied($first);
            $migrations->removeApplied($second);
        }
    }
}

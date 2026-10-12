<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <Deltablot>
 * @author Mouss <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\Migrations;
use Elabftw\Elabftw\SchemaVersionChecker;
use Elabftw\Models\Config;
use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

class DatabaseMigrationCommandsTest extends TestCase
{
    private Fs $fs;

    protected function setUp(): void
    {
        $this->fs = new Fs(new InMemoryFilesystemAdapter());
    }

    public function testStatusReportsEmptyFilesystem(): void
    {
        $tester = new CommandTester(new StatusDatabase($this->fs));
        $tester->execute(array());
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('No migrations found.', $tester->getDisplay());
    }

    public function testStatusReportsAppliedAndPendingMigrations(): void
    {
        $applied = Migrations::generateUuidV7();
        $pending = Migrations::generateUuidV7();
        $this->fs->write(sprintf('migration-%s_applied.sql', $applied), 'SELECT 1;');
        $this->fs->write(sprintf('migration-%s_pending.sql', $pending), 'SELECT 1;');
        $migrations = new Migrations($this->fs);

        try {
            $migrations->recordApplied($applied, $migrations->nextBatch());
            $tester = new CommandTester(new StatusDatabase($this->fs));
            $tester->execute(array());
            $tester->assertCommandIsSuccessful();
            $this->assertStringContainsString(sprintf('applied %s migration-%s_applied.sql', $applied, $applied), $tester->getDisplay());
            $this->assertStringContainsString(sprintf('pending %s migration-%s_pending.sql', $pending, $pending), $tester->getDisplay());
        } finally {
            $migrations->removeApplied($applied);
        }
    }

    public function testRevertRejectsInvalidIdentifier(): void
    {
        $tester = new CommandTester(new RevertSchema($this->fs));
        $this->assertSame(Command::INVALID, $tester->execute(array('migration' => 'not-a-uuid')));
        $this->assertStringContainsString('must be a UUIDv7', $tester->getDisplay());
    }

    public function testRevertRejectsPendingMigration(): void
    {
        $uuid = Migrations::generateUuidV7();
        $this->fs->write(sprintf('migration-%s.sql', $uuid), 'SELECT 1;');
        $tester = new CommandTester(new RevertSchema($this->fs));
        $this->assertSame(Command::FAILURE, $tester->execute(array('migration' => $uuid)));
        $this->assertStringContainsString('is not applied.', $tester->getDisplay());
    }

    public function testRevertRejectsMissingMigrationFile(): void
    {
        $uuid = Migrations::generateUuidV7();
        $migrations = new Migrations($this->fs);
        try {
            $migrations->recordApplied($uuid, $migrations->nextBatch());
            $tester = new CommandTester(new RevertSchema($this->fs));
            $this->assertSame(Command::FAILURE, $tester->execute(array('migration' => $uuid)));
            $this->assertStringContainsString('was not found.', $tester->getDisplay());
            $this->assertTrue($migrations->isApplied($uuid));
        } finally {
            $migrations->removeApplied($uuid);
        }
    }

    public function testRevertAppliedMigrationRemovesItsRecord(): void
    {
        $uuid = Migrations::generateUuidV7();
        $this->writeMigration($uuid);
        $migrations = new Migrations($this->fs);
        try {
            $migrations->recordApplied($uuid, $migrations->nextBatch());
            $tester = new CommandTester(new RevertSchema($this->fs));
            $tester->execute(array('migration' => $uuid));
            $tester->assertCommandIsSuccessful();
            $this->assertStringContainsString(sprintf('Reverted migration %s.', $uuid), $tester->getDisplay());
            $this->assertFalse($migrations->isApplied($uuid));
        } finally {
            $migrations->removeApplied($uuid);
        }
    }

    public function testRevertLatestBatchRevertsAllMigrations(): void
    {
        $first = Migrations::generateUuidV7();
        $second = Migrations::generateUuidV7();
        $this->writeMigration($first);
        $this->writeMigration($second);
        $migrations = new Migrations($this->fs);
        try {
            $batch = $migrations->nextBatch();
            $migrations->recordApplied($first, $batch);
            $migrations->recordApplied($second, $batch);
            $tester = new CommandTester(new RevertSchema($this->fs));
            $tester->execute(array());
            $tester->assertCommandIsSuccessful();
            $this->assertStringContainsString(sprintf('Reverted migration %s.', $first), $tester->getDisplay());
            $this->assertStringContainsString(sprintf('Reverted migration %s.', $second), $tester->getDisplay());
            $this->assertFalse($migrations->isApplied($first));
            $this->assertFalse($migrations->isApplied($second));
        } finally {
            $migrations->removeApplied($first);
            $migrations->removeApplied($second);
        }
    }

    public function testForceSchemaRejectsInvalidAndUnknownIdentifiers(): void
    {
        $tester = new CommandTester(new ForceSchema());
        $this->assertSame(Command::INVALID, $tester->execute(array('identifier' => 'invalid')));
        $this->assertStringContainsString('Identifier must be a UUIDv7', $tester->getDisplay());

        $uuid = Migrations::generateUuidV7();
        $this->assertSame(Command::FAILURE, $tester->execute(array('identifier' => $uuid)));
        $this->assertStringContainsString('was not found.', $tester->getDisplay());
    }

    public function testUpdateDatabaseSkipsAnUpToDateDatabase(): void
    {
        $current = (int) Config::getConfig()->configArr['schema'];
        $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, $current);
        $app = new Application();
        $app->add(new CheckDatabase($current));
        $app->add(new UpdateDatabase());

        $tester = new CommandTester($app->find('db:update'));
        $tester->execute(array());
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('No upgrade required.', $tester->getDisplay());
    }

    public function testUpdateDatabaseRunsUpdateWhenCheckFails(): void
    {
        // Trigger the update command's branch without changing the real schema.
        // The DB is already current and this filesystem has no pending migrations.
        $this->assertSame(SchemaVersionChecker::REQUIRED_SCHEMA, (int) Config::getConfig()->configArr['schema']);
        $check = new class ('db:check') extends Command {
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return Command::FAILURE;
            }
        };
        $app = new Application();
        $app->add($check);
        $app->add(new UpdateDatabase());

        $tester = new CommandTester($app->find('db:update'));
        $tester->execute(array());
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Database update starting', $tester->getDisplay());
        $this->assertStringContainsString('Database is up to date', $tester->getDisplay());
    }

    private function writeMigration(string $uuid): void
    {
        $this->fs->write(sprintf('migration-%s.sql', $uuid), 'SELECT 1;');
        $this->fs->write(sprintf('migration-%s.down.sql', $uuid), 'SELECT 1;');
    }
}

<?php

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Commands\CheckDatabase;
use Elabftw\Commands\ForceSchema;
use Elabftw\Commands\RevertSchema;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;
use PDO;

use function dirname;
use function str_replace;

class MigrationsTest extends TestCase
{
    private const string FIRST = '2026_09_14_090000_first';

    private const string SECOND = '2026_09_14_100000_second';

    private Filesystem $fs;

    private Migrations $migrations;

    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->fs = new Filesystem(new InMemoryFilesystemAdapter());
        $this->fs->write('migrations.sql', FsTools::getFs(dirname(__DIR__, 3) . '/src/sql')->read('migrations.sql'));
        (new Sql($this->fs, new NullOutput()))->execFile('migrations.sql');
        // Shadow the real history so tests never remove installed application migrations.
        $this->Db->q(str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TEMPORARY TABLE', $this->fs->read('migrations.sql')));
        $this->Db->q('CREATE TEMPORARY TABLE migration_test_log (value varchar(20))');
        $this->migrations = new Migrations($this->fs, new NullOutput());
    }

    protected function tearDown(): void
    {
        $this->Db->q('DROP TEMPORARY TABLE IF EXISTS schema_migrations, migration_test_log');
        $this->Db->q('DROP TABLE IF EXISTS migration_test_created');
    }

    public function testIndependentMigrationsAndIdempotentUpdate(): void
    {
        // Deliberately write files in the opposite order.
        $this->add(self::SECOND, 'second');
        $this->add(self::FIRST, 'first');
        self::assertSame(array(self::FIRST, self::SECOND), $this->migrations->pending());
        self::assertSame(2, $this->migrations->migrate());
        self::assertSame(array(self::FIRST => 1, self::SECOND => 1), $this->migrations->applied());
        self::assertSame(0, $this->migrations->migrate());
        self::assertSame(array('first', 'second'), $this->values());
    }

    public function testOlderMigrationMergedLaterStillRunsAndRollsBackFirst(): void
    {
        $this->add(self::SECOND, 'second');
        $this->migrations->migrate();
        $this->add(self::FIRST, 'first');
        self::assertSame(1, $this->migrations->migrate());
        self::assertSame(array(self::SECOND => 1, self::FIRST => 2), $this->migrations->applied());
        self::assertSame(1, $this->migrations->rollback());
        self::assertSame(array('second'), $this->values());
        self::assertSame(array(self::FIRST), $this->migrations->pending());
    }

    public function testBatchRollbackUsesReverseExecutionOrder(): void
    {
        $this->add(self::FIRST, 'first');
        $this->add(self::SECOND, 'second');
        $this->fs->write('migrations/' . self::FIRST . '-down.sql', "INSERT INTO migration_test_log VALUES ('down_first');");
        $this->fs->write('migrations/' . self::SECOND . '-down.sql', "INSERT INTO migration_test_log VALUES ('down_second');");
        $this->migrations->migrate();
        self::assertSame(2, $this->migrations->rollback());
        self::assertSame(array('first', 'second', 'down_second', 'down_first'), $this->values());
        self::assertSame(array(), $this->migrations->applied());
    }

    public function testStepBatchesAndRollbackCount(): void
    {
        $this->add(self::FIRST, 'first');
        $this->add(self::SECOND, 'second');
        $this->migrations->migrate(true);
        self::assertSame(array(self::FIRST => 1, self::SECOND => 2), $this->migrations->applied());
        self::assertSame(2, $this->migrations->rollback(steps: 2));
        self::assertSame(array(), $this->values());
    }

    public function testFailedMigrationIsCompensatedAndNotRecorded(): void
    {
        $this->add(self::FIRST, 'first');
        $this->add(self::SECOND, 'second');
        $this->fs->write('migrations/' . self::SECOND . '.sql', "CREATE TABLE migration_test_created (id int);\nSELECT migration_test_missing_column FROM migration_test_created;");
        $this->fs->write('migrations/' . self::SECOND . '-down.sql', 'DROP TABLE IF EXISTS migration_test_created;');
        try {
            $this->migrations->migrate();
            self::fail('The invalid migration must fail.');
        } catch (DatabaseErrorException) {
            self::assertSame(array(self::FIRST => 1), $this->migrations->applied());
            self::assertSame(array(self::SECOND), $this->migrations->pending());
            self::assertSame(0, (int) $this->Db->q("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'migration_test_created'")->fetchColumn());
        }
        // A second attempt demonstrates that the command also released its lock.
        $this->add(self::SECOND, 'second');
        self::assertSame(1, $this->migrations->migrate());
    }

    public function testFailedRollbackRetainsHistory(): void
    {
        $this->add(self::FIRST, 'first');
        $this->migrations->migrate();
        $this->fs->write('migrations/' . self::FIRST . '-down.sql', 'SELECT migration_test_missing_column FROM migration_test_log;');
        try {
            $this->migrations->rollback();
            self::fail('The invalid rollback must fail.');
        } catch (DatabaseErrorException) {
            self::assertSame(array(self::FIRST => 1), $this->migrations->applied());
        }
    }

    public function testMissingDownFileIsDetectedBeforeAnyMigrationRuns(): void
    {
        $this->add(self::FIRST, 'first');
        $this->add(self::SECOND, 'second');
        $this->fs->delete('migrations/' . self::SECOND . '-down.sql');
        try {
            $this->migrations->migrate();
            self::fail('A missing down file must fail validation.');
        } catch (ImproperActionException) {
            self::assertSame(array(), $this->values());
            self::assertSame(array(), $this->migrations->applied());
        }
    }

    public function testCannotRevertAnOlderMigrationById(): void
    {
        $this->add(self::FIRST, 'first');
        $this->add(self::SECOND, 'second');
        $this->migrations->migrate();
        $this->expectException(ImproperActionException::class);
        $this->migrations->rollback(self::FIRST);
    }

    public function testCannotDowngradeLegacySchemaWhileMigrationsRemain(): void
    {
        $this->add(self::FIRST, 'first');
        $this->migrations->migrate();
        $this->expectException(ImproperActionException::class);
        $this->migrations->revertLegacy((string) SchemaVersionChecker::REQUIRED_SCHEMA);
    }

    public function testCannotForceLegacySchemaWhileMigrationsRemain(): void
    {
        $this->add(self::FIRST, 'first');
        $this->migrations->migrate();
        $this->expectException(ImproperActionException::class);
        $this->migrations->forceLegacy('200');
    }

    public function testForceCommandOnlyChangesHistory(): void
    {
        $this->add(self::FIRST, 'first');
        $command = new CommandTester(new ForceSchema($this->fs));
        $command->execute(array('schema' => self::FIRST));
        $command->assertCommandIsSuccessful();
        self::assertSame(array(self::FIRST => 1), $this->migrations->applied());
        self::assertSame(array(), $this->values());
        $command->execute(array('schema' => self::FIRST, '--forget' => true));
        $command->assertCommandIsSuccessful();
        self::assertSame(array(self::FIRST), $this->migrations->pending());
    }

    public function testRollbackCommandDefaultsToLastBatch(): void
    {
        $this->add(self::FIRST, 'first');
        $this->migrations->migrate();
        $command = new CommandTester(new RevertSchema($this->fs));
        $command->execute(array());
        $command->assertCommandIsSuccessful();
        self::assertSame(array(), $this->migrations->applied());
    }

    public function testCheckCommandReportsPendingAndApplied(): void
    {
        $this->add(self::FIRST, 'first');
        $command = new CommandTester(new CheckDatabase(SchemaVersionChecker::REQUIRED_SCHEMA, $this->migrations));
        self::assertSame(1, $command->execute(array()));
        self::assertStringContainsString('Pending', $command->getDisplay());
        $this->migrations->migrate();
        self::assertSame(0, $command->execute(array()));
        self::assertStringContainsString('Applied (batch 1)', $command->getDisplay());
    }

    public function testCheckerRejectsPendingMigrationsAtSameLegacyVersion(): void
    {
        $this->add(self::FIRST, 'first');
        $this->expectException(InvalidSchemaException::class);
        (new SchemaVersionChecker(SchemaVersionChecker::REQUIRED_SCHEMA, $this->migrations))->checkSchema();
    }

    public function testUnknownAppliedMigrationBlocksUpdate(): void
    {
        $this->add(self::FIRST, 'first');
        $this->migrations->migrate();
        $this->fs->delete('migrations/' . self::FIRST . '.sql');
        self::assertFalse($this->migrations->isCurrent());
        $command = new CommandTester(new CheckDatabase(SchemaVersionChecker::REQUIRED_SCHEMA, $this->migrations));
        self::assertSame(2, $command->execute(array()));
        $this->expectException(ImproperActionException::class);
        $this->migrations->migrate();
    }

    private function add(string $name, string $value): void
    {
        $this->fs->write('migrations/' . $name . '.sql', "INSERT INTO migration_test_log VALUES ('$value');");
        $this->fs->write('migrations/' . $name . '-down.sql', "DELETE FROM migration_test_log WHERE value = '$value';");
    }

    private function values(): array
    {
        return $this->Db->q('SELECT value FROM migration_test_log')->fetchAll(PDO::FETCH_COLUMN);
    }
}

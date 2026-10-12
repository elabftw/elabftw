<?php

declare(strict_types=1);

namespace Elabftw\Elabftw;

use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

use function array_count_values;
use function array_keys;
use function sprintf;

/**
 * Exercise discovery and database tracking together, not just UUID generation.
 */
class MigrationsStateTest extends TestCase
{
    public function testDiscoveryIgnoresNonMigrationFilesAndSubdirectories(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $first = '019d0000-0000-7000-8000-000000000001';
        $second = '019d0000-0001-7000-8000-000000000002';
        $fs->write(sprintf('migration-%s_second-change.sql', $second), 'SELECT 2;');
        $fs->write(sprintf('migration-%s_first-change.sql', $first), 'SELECT 1;');
        $fs->write(sprintf('migration-%s_first-change.down.sql', $first), 'SELECT 1;');
        $fs->write('migration-not-a-uuid.sql', 'SELECT 1;');
        $fs->write(sprintf('migration-%s_INVALID.sql', $first), 'SELECT 1;');
        $fs->write(sprintf('nested/migration-%s.sql', $first), 'SELECT 1;');

        $migrations = new Migrations($fs);
        $this->assertSame(array(
            $first => sprintf('migration-%s_first-change.sql', $first),
            $second => sprintf('migration-%s_second-change.sql', $second),
        ), $migrations->getAvailable());
        $this->assertSame(sprintf('migration-%s_first-change.sql', $first), $migrations->getFilename($first));
        $this->assertNull($migrations->getFilename('missing'));
        $this->assertFalse(Migrations::isUuidV7('not-a-uuid'));
    }

    public function testNoMigrationsArePendingWithAnEmptyFilesystem(): void
    {
        $migrations = new Migrations(new Fs(new InMemoryFilesystemAdapter()));
        $this->assertSame(array(), $migrations->getPending());
        $this->assertSame(0, $migrations->getPendingCount());
        $migrations->markAllAvailableAsApplied();
    }

    public function testAppliedStatePendingCountAndLastBatch(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $first = Migrations::generateUuidV7();
        $second = Migrations::generateUuidV7();
        $fs->write(sprintf('migration-%s.sql', $first), 'SELECT 1;');
        $fs->write(sprintf('migration-%s.sql', $second), 'SELECT 2;');
        $migrations = new Migrations($fs);

        try {
            $this->assertSame(2, $migrations->getPendingCount());
            $batch = $migrations->nextBatch();
            $migrations->recordApplied($first, $batch);

            $this->assertTrue($migrations->isApplied($first));
            $this->assertContains($first, $migrations->getApplied());
            $this->assertSame(array($second), array_keys($migrations->getPending()));
            $this->assertSame(1, $migrations->getPendingCount());

            $migrations->recordApplied($second, $migrations->nextBatch());
            $this->assertSame(array($second), $migrations->getLastBatch());
            $this->assertSame(array(), $migrations->getPending());
            $this->assertSame(0, $migrations->getPendingCount());

            $migrations->removeApplied($first);
            $this->assertFalse($migrations->isApplied($first));
            $this->assertSame(array($first), array_keys($migrations->getPending()));
        } finally {
            $migrations->removeApplied($first);
            $migrations->removeApplied($second);
        }
    }

    public function testMarkAllAvailableAsAppliedIsIdempotent(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $uuid = Migrations::generateUuidV7();
        $fs->write(sprintf('migration-%s.sql', $uuid), 'SELECT 1;');
        $migrations = new Migrations($fs);

        try {
            $migrations->markAllAvailableAsApplied();
            $this->assertTrue($migrations->isApplied($uuid));
            $this->assertSame(0, $migrations->getPendingCount());
            $migrations->markAllAvailableAsApplied();
            $this->assertSame(1, array_count_values($migrations->getApplied())[$uuid]);
        } finally {
            $migrations->removeApplied($uuid);
        }
    }
}

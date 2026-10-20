<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use League\Flysystem\Filesystem as Fs;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

use function array_keys;
use function sprintf;

class MigrationsTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerateUuidV7(): void
    {
        $uuid = Migrations::generateUuidV7();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
        );
        $this->assertTrue(Migrations::isUuidV7($uuid));
    }

    public function testDiscoverMigrations(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $first = '019d0000-0000-7000-8000-000000000001';
        $second = '019d0000-0001-7000-8000-000000000002';
        $fs->write(sprintf('migration-%s_second.sql', $second), 'SELECT 2;');
        $fs->write(sprintf('migration-%s_first.sql', $first), 'SELECT 1;');
        $fs->write(sprintf('migration-%s_first.down.sql', $first), 'SELECT 1;');
        $fs->write('schema225.sql', 'SELECT 3;');

        $available = new Migrations($fs, useBuildInfo: false)->getAvailable();

        $this->assertSame(array($first, $second), array_keys($available));
    }

    public function testDownFilename(): void
    {
        $this->assertSame(
            'migration-019d0000-0000-7000-8000-000000000001_test.down.sql',
            Migrations::getDownFilename('migration-019d0000-0000-7000-8000-000000000001_test.sql'),
        );
    }

    public function testBuildManifestDoesNotDiscoverFiles(): void
    {
        $fs = $this->createMock(Fs::class);
        $fs->expects($this->never())->method('listContents');

        $this->assertSame(BuildInfo::MIGRATIONS, new Migrations($fs, useBuildInfo: true)->getAvailable());
    }

    public function testFilesystemDiscoveryFindsNewFiles(): void
    {
        $fs = new Fs(new InMemoryFilesystemAdapter());
        $first = '019d0000-0000-7000-8000-000000000001';
        $second = '019d0000-0001-7000-8000-000000000002';
        $fs->write(sprintf('migration-%s_first.sql', $first), 'SELECT 1;');
        $Migrations = new Migrations($fs, useBuildInfo: false);
        $this->assertSame(array($first), array_keys($Migrations->getAvailable()));

        // Filesystem discovery must notice new files without rebuilding BuildInfo.
        $fs->write(sprintf('migration-%s_second.sql', $second), 'SELECT 2;');
        $this->assertSame(array($first, $second), array_keys($Migrations->getAvailable()));
    }
}

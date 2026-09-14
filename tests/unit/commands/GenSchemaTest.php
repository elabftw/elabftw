<?php

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Exceptions\ImproperActionException;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class GenSchemaTest extends TestCase
{
    public function testGeneratesIndependentPairsWithoutSchemaNumber(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $command = new CommandTester(new GenSchema($fs));
        foreach (array('add_booking_color', 'add_container_expiry') as $name) {
            $command->execute(array('name' => $name));
            $command->assertCommandIsSuccessful();
        }
        $files = $fs->listContents('migrations')->toArray();
        self::assertCount(4, $files);
        foreach ($files as $file) {
            self::assertMatchesRegularExpression('/^migrations\/\d{4}_\d{2}_\d{2}_\d{6}_add_(booking_color|container_expiry)(-down)?\.sql$/', $file->path());
            self::assertStringNotContainsString('UPDATE config', $fs->read($file->path()));
        }
    }

    public function testNeverOverwritesAnExistingMigration(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('fileExists')->willReturn(true);
        $fs->expects($this->never())->method('write');
        $command = new CommandTester(new GenSchema($fs));
        $this->expectException(ImproperActionException::class);
        $command->execute(array('name' => 'add_booking_color'));
    }

    public function testRejectsPathTraversal(): void
    {
        $command = new CommandTester(new GenSchema(new Filesystem(new InMemoryFilesystemAdapter())));
        $this->expectException(ImproperActionException::class);
        $command->execute(array('name' => '../schema225'));
    }
}

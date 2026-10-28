<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Exceptions\ImproperActionException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use function dirname;
use function fread;
use function glob;
use function is_resource;
use function str_repeat;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class ZipImportTest extends TestCase
{
    private array $archives = array();

    protected function tearDown(): void
    {
        foreach ($this->archives as $path) {
            unlink($path);
        }
    }

    /**
     * @dataProvider limitProvider
     */
    public function testRejectsArchiveBeforeWriting(ZipLimits $limits, array $members, string $message): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->expects($this->never())->method('createDirectory');
        $fs->expects($this->never())->method('writeStream');
        $importer = $this->createImporter($this->archive($members), $fs, $limits);
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage($message);
        $importer->prepare();
    }

    public static function limitProvider(): array
    {
        return array(
            'directories count as members' => array(new ZipLimits(maxMembers: 1), array('root/' => '', 'root/file' => 'a'), 'member count'),
            'member bytes' => array(new ZipLimits(maxMemberBytes: 4), array('root/file' => '12345'), 'member exceeds'),
            'total bytes across members' => array(new ZipLimits(maxMemberBytes: 4, maxTotalBytes: 7), array('root/a' => '1234', 'root/b' => '5678'), 'total uncompressed'),
            'compression ratio' => array(new ZipLimits(maxCompressionRatio: 2), array('root/file' => str_repeat('a', 1024 * 1024 + 1)), 'compression ratio'),
            'unsafe path' => array(new ZipLimits(), array('../outside' => 'a'), 'unsafe path'),
        );
    }

    /**
     * @dataProvider smallMemberSizeProvider
     */
    public function testSmallMembersAreExemptFromCompressionRatioLimit(int $size): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $payload = str_repeat('a', $size);
        $importer = $this->createImporter(
            $this->archive(array('root/ro-crate-metadata.json' => '{"@graph":[]}', 'root/payload' => $payload)),
            $fs,
            new ZipLimits(maxCompressionRatio: 2),
        );
        try {
            $importer->prepare();
            $directory = new ReflectionProperty(AbstractZip::class, 'tmpDir')->getValue($importer);
            $this->assertSame($payload, $fs->read($directory . '/root/payload'));
        } finally {
            $importer->cleanup();
        }
    }

    public static function smallMemberSizeProvider(): array
    {
        return array(
            'below 1 MiB' => array(65536),
            'exactly 1 MiB' => array(1024 * 1024),
        );
    }

    /**
     * @dataProvider unsafeFileIdProvider
     */
    public function testRejectsUnsafeFileIdBeforeReadingStorage(string $id): void
    {
        foreach (array(Eln::class, TrustedEln::class) as $class) {
            $fs = $this->createMock(FilesystemOperator::class);
            $fs->expects($this->never())->method('readStream');
            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->once())->method('error')->with('Skipping file with unsafe @id: ' . $id);
            $importer = new ReflectionClass($class)->newInstanceWithoutConstructor();
            new ReflectionProperty(AbstractZip::class, 'tmpFs')->setValue($importer, $fs);
            new ReflectionProperty(AbstractZip::class, 'logger')->setValue($importer, $logger);
            new ReflectionProperty(AbstractZip::class, 'tmpDir')->setValue($importer, 'workspace');
            new ReflectionProperty(Eln::class, 'root')->setValue($importer, 'crate');

            new ReflectionMethod(Eln::class, 'importFile')->invoke($importer, array('@id' => $id));
        }
    }

    public static function unsafeFileIdProvider(): array
    {
        return array(
            'empty' => array(''),
            'NUL' => array("file\0name"),
            'parent traversal' => array('../../known-export'),
            'nested parent traversal' => array('uploads/../../../known-export'),
            'backslash traversal' => array('..\\..\\known-export'),
            'backslash separator' => array('uploads\\known-export'),
            'absolute path' => array('/known-export'),
            'Windows drive path' => array('C:/known-export'),
            'Windows drive-relative path' => array('C:known-export'),
            'encoded traversal' => array('%2e%2e/%2e%2e/known-export'),
            'double encoded traversal' => array('%252e%252e/%252e%252e/known-export'),
            'encoded absolute path' => array('%2fknown-export'),
            'encoded backslash' => array('uploads%5cknown-export'),
            'encoded NUL' => array('file%00name'),
        );
    }

    public function testTrustedImportBypassesAllLimits(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $metadata = '{"@graph":[]}';
        $payload = str_repeat('a', 65536);
        $importer = $this->createTrustedImporter(
            $this->archive(array('root/ro-crate-metadata.json' => $metadata, 'root/payload' => $payload)),
            $fs,
            new ZipLimits(maxMembers: 1, maxMemberBytes: 1, maxTotalBytes: 1, maxCompressionRatio: 1, maxMetadataBytes: 1),
        );
        try {
            $importer->prepare();
            $directory = new ReflectionProperty(AbstractZip::class, 'tmpDir')->getValue($importer);
            $this->assertSame($metadata, $fs->read($directory . '/root/ro-crate-metadata.json'));
            $this->assertSame($payload, $fs->read($directory . '/root/payload'));
            $this->assertSame(0, $importer->getCount());
        } finally {
            $importer->cleanup();
        }
        $this->assertSame(array(), $fs->listContents('', true)->toArray());
    }

    /**
     * @dataProvider invalidTrustedArchiveProvider
     */
    public function testTrustedImportStillValidatesArchive(array $members, string $message): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->expects($this->never())->method('createDirectory');
        $fs->expects($this->never())->method('writeStream');
        $importer = $this->createTrustedImporter($this->archive($members), $fs, new ZipLimits());
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage($message);
        $importer->prepare();
    }

    public static function invalidTrustedArchiveProvider(): array
    {
        return array(
            'unsafe path' => array(array('../outside' => 'a'), 'unsafe path'),
            'missing metadata' => array(array('root/payload' => 'data'), 'missing ro-crate-metadata.json'),
            'invalid graph' => array(array('root/ro-crate-metadata.json' => '{}'), '@graph array'),
            'invalid graph node' => array(array('root/ro-crate-metadata.json' => '{"@graph":[{}]}'), 'invalid graph node'),
        );
    }

    /**
     * @dataProvider metadataProvider
     */
    public function testInvalidMetadataDoesNotExtractAttachments(array $members, ZipLimits $limits): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->expects($this->never())->method('createDirectory');
        $fs->expects($this->never())->method('writeStream');
        $members['root/payload'] = str_repeat('a', 65536);
        $importer = $this->createImporter($this->archive($members), $fs, $limits);
        $this->expectException(ImproperActionException::class);
        $importer->import();
    }

    public static function metadataProvider(): array
    {
        return array(
            'missing metadata' => array(array(), new ZipLimits()),
            'invalid JSON' => array(array('root/ro-crate-metadata.json' => '{'), new ZipLimits()),
            'missing graph' => array(array('root/ro-crate-metadata.json' => '{}'), new ZipLimits()),
            'invalid graph node' => array(array('root/ro-crate-metadata.json' => '{"@graph":[{}]}'), new ZipLimits()),
            'metadata size limit' => array(array('root/ro-crate-metadata.json' => '{"@graph":[]}'), new ZipLimits(maxMetadataBytes: 4)),
        );
    }

    public function testPrepareAndCleanupAreIdempotent(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $fs->write('existing-export', 'keep');
        $members = array('root/ro-crate-metadata.json' => '{"@graph":[]}', 'root/payload' => 'data');
        $importer = $this->createImporter($this->archive($members), $fs);
        $importer->prepare();
        $directory = new ReflectionProperty(AbstractZip::class, 'tmpDir')->getValue($importer);
        $importer->prepare();
        $this->assertSame($directory, new ReflectionProperty(AbstractZip::class, 'tmpDir')->getValue($importer));
        $this->assertSame('data', $fs->read($directory . '/root/payload'));
        $importer->cleanup();
        $importer->cleanup();
        $this->assertFalse($fs->directoryExists($directory));
        $this->assertSame('keep', $fs->read('existing-export'));
    }

    public function testSuccessfulImportCleansUpWhileObjectIsAlive(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $importer = $this->createImporter($this->archive(array('root/ro-crate-metadata.json' => '{"@graph":[]}')), $fs);
        $this->assertSame(0, $importer->import());
        $this->assertSame(array(), $fs->listContents('', true)->toArray());
    }

    public function testLocalFilesystemCleanupPreservesOtherExports(): void
    {
        $root = tempnam(sys_get_temp_dir(), 'elabftw-export-storage-');
        $this->assertIsString($root);
        unlink($root);
        $fs = new Filesystem(new LocalFilesystemAdapter($root));
        $fs->write('existing-export', 'keep');
        $importer = $this->createImporter($this->archive(array('root/ro-crate-metadata.json' => '{"@graph":[]}', 'root/payload' => 'data')), $fs);
        try {
            $this->assertSame(0, $importer->import());
            $this->assertSame('keep', $fs->read('existing-export'));
            $this->assertCount(1, $fs->listContents('', true)->toArray());
        } finally {
            $importer->cleanup();
            $fs->deleteDirectory('');
        }
    }

    public function testImportExceptionCleansUpWhileObjectIsAlive(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $importer = $this->getMockBuilder(Eln::class)->disableOriginalConstructor()->onlyMethods(array('getAuthor'))->getMock();
        $importer->expects($this->once())->method('getAuthor')->willThrowException(new RuntimeException('import failed'));
        $metadata = '{"@graph":[{"@id":"./","hasPart":[{"@id":"record"}]},{"@id":"record","@type":"Dataset"}]}';
        $this->initializeImporter($importer, $this->archive(array('root/ro-crate-metadata.json' => $metadata)), $fs, new ZipLimits());
        try {
            $importer->import();
            $this->fail('The import should have failed.');
        } catch (RuntimeException $e) {
            $this->assertSame('import failed', $e->getMessage());
            $this->assertSame(array(), $fs->listContents('', true)->toArray());
        }
    }

    public function testPartialWriteFailureClosesStreamAndDeletesWorkspace(): void
    {
        $backing = new Filesystem(new InMemoryFilesystemAdapter());
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->method('createDirectory')->willReturnCallback($backing->createDirectory(...));
        $source = null;
        $fs->method('writeStream')->willReturnCallback(function (string $path, $stream) use (&$source, $backing): void {
            $source = $stream;
            $this->assertSame('{', fread($stream, 1));
            $backing->write($path, 'partial write');
            throw UnableToWriteFile::atLocation($path, 'No space left on device');
        });
        $fs->expects($this->once())->method('deleteDirectory')->with($this->matchesRegularExpression('/^[a-f0-9-]{36}$/'))->willReturnCallback($backing->deleteDirectory(...));
        $importer = $this->createImporter($this->archive(array('root/ro-crate-metadata.json' => '{"@graph":[]}')), $fs);
        try {
            $importer->prepare();
            $this->fail('The write should have failed.');
        } catch (UnableToWriteFile $e) {
            $this->assertStringContainsString('No space left on device', $e->getMessage());
            $this->assertFalse(is_resource($source));
            $this->assertSame(array(), $backing->listContents('', true)->toArray());
        }
    }

    /**
     * @dataProvider streamingLimitProvider
     */
    public function testStreamingLimitsAreIndependentOfPreflight(ZipLimits $limits): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $path = $this->archive(array('root/a' => '123', 'root/b' => '4567'));
        $importer = $this->createImporter($path, $fs, $limits);
        new ReflectionProperty(AbstractZip::class, 'tmpDir')->setValue($importer, 'workspace');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::RDONLY));
        try {
            // Bypass header checks to exercise the actual decompression boundary.
            new ReflectionMethod(AbstractZip::class, 'extractArchive')->invoke($importer, $zip);
            $this->fail('The decompression budget was exceeded.');
        } catch (ImproperActionException) {
            $this->assertSame('123', $fs->read('workspace/root/a'));
            $this->assertSame('', $fs->read('workspace/root/b'));
        } finally {
            $zip->close();
            $importer->cleanup();
        }
        $this->assertSame(array(), $fs->listContents('', true)->toArray());
    }

    public static function streamingLimitProvider(): array
    {
        return array(
            'total stream budget' => array(new ZipLimits(maxMemberBytes: 4, maxTotalBytes: 6)),
            'individual stream budget' => array(new ZipLimits(maxMemberBytes: 3)),
        );
    }

    public function testExistingElnFixturesPrepareSuccessfully(): void
    {
        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $fixtures = glob(dirname(__DIR__, 2) . '/_data/*.eln');
        $this->assertIsArray($fixtures);
        foreach ($fixtures as $path) {
            $importer = $this->createImporter($path, $fs);
            try {
                $importer->prepare();
                $this->assertGreaterThan(0, $importer->getCount(), $path);
            } finally {
                $importer->cleanup();
            }
            $this->assertSame(array(), $fs->listContents('', true)->toArray());
        }
    }

    private function createImporter(string $path, FilesystemOperator $fs, ?ZipLimits $limits = null): Eln
    {
        $importer = new ReflectionClass(Eln::class)->newInstanceWithoutConstructor();
        $this->initializeImporter($importer, $path, $fs, $limits ?? new ZipLimits());
        return $importer;
    }

    private function initializeImporter(Eln $importer, string $path, FilesystemOperator $fs, ZipLimits $limits): void
    {
        // Extraction needs no database: supply its inputs without constructing Users/Db.
        foreach (array('UploadedFile' => new UploadedFile($path, 'import.eln', test: true), 'tmpFs' => $fs, 'limits' => $limits, 'logger' => new NullLogger()) as $name => $value) {
            new ReflectionProperty($importer, $name)->setValue($importer, $value);
        }
    }

    private function createTrustedImporter(string $path, FilesystemOperator $fs, ZipLimits $limits): TrustedEln
    {
        $importer = new ReflectionClass(TrustedEln::class)->newInstanceWithoutConstructor();
        $this->initializeImporter($importer, $path, $fs, $limits);
        return $importer;
    }

    private function archive(array $members): string
    {
        $path = tempnam(sys_get_temp_dir(), 'elabftw-zip-limit-');
        $this->assertIsString($path);
        $this->archives[] = $path;
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::OVERWRITE));
        foreach ($members as $name => $contents) {
            $this->assertTrue($zip->addFromString($name, $contents));
        }
        $this->assertTrue($zip->close());
        return $path;
    }
}

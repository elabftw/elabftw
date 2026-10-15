<?php

declare(strict_types=1);

namespace Elabftw\Import;

use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

use function fclose;
use function fopen;
use function fread;
use function is_resource;
use function strlen;
use function stream_get_contents;
use function stream_get_meta_data;
use function str_repeat;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class LimitedReadStreamTest extends TestCase
{
    public function testExactLimitThroughPhpResource(): void
    {
        $contents = str_repeat('a', 20000);
        $stream = new LimitedReadStream(Utils::streamFor($contents)->detach(), 20000);
        $resource = $stream->getResource();
        try {
            $this->assertSame($contents, stream_get_contents($resource));
            $stream->assertFullyRead();
        } finally {
            fclose($resource);
            $stream->close();
        }
    }

    public function testRejectsBytesBeforeReturningThem(): void
    {
        $stream = new LimitedReadStream(Utils::streamFor('12345')->detach(), 4);
        fread($stream->getResource(), 4);
        $this->expectException(ImproperActionException::class);
        $stream->assertNoFailure();
    }

    public function testGetContentsAlsoEnforcesLimit(): void
    {
        $stream = new LimitedReadStream(Utils::streamFor(str_repeat('a', 20000))->detach(), 10000);
        $this->expectException(ImproperActionException::class);
        $stream->getContents();
    }

    public function testFailureCannotBeIgnoredOrRetried(): void
    {
        $stream = new LimitedReadStream(Utils::streamFor('12345')->detach(), 4);
        try {
            $stream->getContents();
            $this->fail('The oversized stream was accepted.');
        } catch (ImproperActionException) {
            $this->expectException(ImproperActionException::class);
            $stream->assertFullyRead();
        }
    }

    public function testDetectsIncompleteConsumption(): void
    {
        $stream = new LimitedReadStream(Utils::streamFor('12345')->detach(), 5);
        fread($stream->getResource(), 1);
        $this->expectException(RuntimeException::class);
        $stream->assertFullyRead();
    }

    public function testClosingWrapperClosesSource(): void
    {
        $source = fopen('php://memory', 'w+b');
        $this->assertIsResource($source);
        $stream = new LimitedReadStream($source, 1);
        $stream->close();
        $this->assertFalse(is_resource($source));
    }

    public function testRejectsInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LimitedReadStream(Utils::streamFor('')->detach(), 0);
    }

    public function testNativeZipStreamSupportsS3Multipart(): void
    {
        $contents = str_repeat('abcd', 5 * 1024 * 1024);
        $path = tempnam(sys_get_temp_dir(), 'elabftw-multipart-limit-');
        $this->assertIsString($path);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::OVERWRITE));
        $this->assertTrue($zip->addFromString('payload', $contents));
        $this->assertTrue($zip->close());
        $this->assertTrue($zip->open($path, ZipArchive::RDONLY));
        $source = $zip->getStreamIndex(0);
        $this->assertIsResource($source);
        $stream = new LimitedReadStream($source, strlen($contents));
        $bytesUploaded = 0;
        $partsUploaded = 0;
        $client = new S3Client(array(
            'version' => '2006-03-01',
            'region' => 'us-east-1',
            'credentials' => array('key' => 'test', 'secret' => 'test'),
            // Exercise the SDK's real multipart streaming without network I/O.
            'handler' => function (CommandInterface $command) use (&$bytesUploaded, &$partsUploaded): FulfilledPromise {
                if ($command->getName() === 'UploadPart') {
                    $bytesUploaded += strlen($command['Body']->getContents());
                    $partsUploaded++;
                }
                return new FulfilledPromise(new Result(array('UploadId' => 'test', 'ETag' => 'test')));
            },
        ));
        try {
            $this->assertFalse(stream_get_meta_data($stream->getResource())['seekable']);
            $client->upload('test-bucket', 'payload', $stream->getResource());
            $stream->assertFullyRead();
            $this->assertSame(strlen($contents), $bytesUploaded);
            $this->assertGreaterThan(1, $partsUploaded);
        } finally {
            $stream->close();
            $zip->close();
            unlink($path);
        }
    }
}

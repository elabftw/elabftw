<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Aws\CommandInterface;
use Aws\Result;
use Aws\S3\S3Client;
use Codeception\Attribute\DataProvider;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function array_filter;
use function bin2hex;
use function count;
use function random_bytes;
use function sprintf;
use function str_replace;
use function str_repeat;
use function sys_get_temp_dir;

class DownloadControllerTest extends \PHPUnit\Framework\TestCase
{
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = $this->getMockBuilder(Filesystem::class)
            ->setConstructorArgs(array(new InMemoryFilesystemAdapter()))
            ->onlyMethods(array('checksum', 'lastModified'))
            ->getMock();
        $this->fs->expects($this->never())->method('checksum');
        $this->fs->expects($this->never())->method('lastModified');
    }

    public function testGetResponse(): void
    {
        $longName = 'aa/aabb.txt';
        $this->fs->write($longName, 'blah');
        $controller = new DownloadController($this->fs, $longName);
        $this->assertInstanceOf(StreamedResponse::class, $this->getResponse($controller));
    }

    public function testGetResponseStreamsContent(): void
    {
        $longName = 'aa/aacc.docx';
        $this->fs->write($longName, 'blah');
        $controller = new DownloadController($this->fs, $longName);
        $response = $this->getResponse($controller);
        ob_start();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('blah', $content);
    }

    #[DataProvider('provideConditionalRequests')]
    public function testConditionalRequests(string $method, array $headers, int $status): void
    {
        $this->fs->write('aa/aafile.txt', 'blah');
        $controller = new DownloadController($this->fs, 'aafile.txt');
        $initial = $this->getResponse($controller);
        $this->assertSame('"aafile.txt"', $initial->getEtag());
        $this->assertNull($initial->getLastModified());
        $request = Request::create('/download', $method);
        foreach ($headers as $name => $value) {
            $request->headers->set($name, str_replace('ETAG', $initial->getEtag(), $value));
        }

        $response = $this->getResponse($controller, $request);
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($initial->getEtag(), $response->getEtag());
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-cache'));
        if ($status === Response::HTTP_NOT_MODIFIED) {
            ob_start();
            $response->sendContent();
            $this->assertSame('', ob_get_contents());
            ob_end_clean();
            $this->assertFalse($response->headers->has('Content-Type'));
            $this->assertFalse($response->headers->has('Content-Length'));
        }
    }

    public static function provideConditionalRequests(): array
    {
        return array(
            'etag' => array('GET', array('If-None-Match' => 'ETAG'), Response::HTTP_NOT_MODIFIED),
            'weak etag' => array('GET', array('If-None-Match' => 'W/ETAG'), Response::HTTP_NOT_MODIFIED),
            'etag list' => array('GET', array('If-None-Match' => '"other", ETAG'), Response::HTTP_NOT_MODIFIED),
            'wildcard' => array('GET', array('If-None-Match' => '*'), Response::HTTP_NOT_MODIFIED),
            'date without etag' => array('GET', array('If-Modified-Since' => 'Fri, 01 Jan 2100 00:00:00 GMT'), Response::HTTP_OK),
            'modified' => array('GET', array('If-Modified-Since' => 'Wed, 01 Jan 1997 00:00:00 GMT'), Response::HTTP_OK),
            'etag takes precedence' => array('GET', array('If-None-Match' => '"other"', 'If-Modified-Since' => 'Fri, 01 Jan 2100 00:00:00 GMT'), Response::HTTP_OK),
            'head' => array('HEAD', array('If-None-Match' => 'ETAG'), Response::HTTP_NOT_MODIFIED),
            'post' => array('POST', array('If-None-Match' => 'ETAG'), Response::HTTP_OK),
        );
    }

    public function testNotModifiedDoesNotOpenStream(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('fileExists')->willReturn(true);
        $fs->expects($this->never())->method('lastModified');
        $fs->expects($this->never())->method('checksum');
        $fs->expects($this->never())->method('readStream');
        $fs->expects($this->never())->method('mimeType');
        $request = Request::create('/download');
        $request->headers->set('If-None-Match', '"aafile.txt"');
        $response = $this->getResponse(new DownloadController($fs, 'aafile.txt'), $request);
        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $response->sendContent();
    }

    public function testLocalFileValidatorsUseLongNameAndCheckExistence(): void
    {
        $root = sprintf('%s/elabftw-download-%s', sys_get_temp_dir(), bin2hex(random_bytes(8)));
        $fs = $this->getMockBuilder(Filesystem::class)
            ->setConstructorArgs(array(new LocalFilesystemAdapter($root)))
            ->onlyMethods(array('checksum', 'lastModified'))
            ->getMock();
        $fs->expects($this->never())->method('checksum');
        $fs->expects($this->never())->method('lastModified');
        try {
            // Also cover legacy files that are not stored in a two-letter subdirectory.
            $fs->write('aafile.txt', 'first');
            $controller = new DownloadController($fs, 'aafile.txt');
            $initial = $this->getResponse($controller);
            $this->assertSame('"aafile.txt"', $initial->getEtag());
            $this->assertNull($initial->getLastModified());

            $request = Request::create('/download');
            $request->headers->set('If-None-Match', $initial->getEtag());
            $this->assertSame(Response::HTTP_NOT_MODIFIED, $this->getResponse($controller, $request)->getStatusCode());

            ob_start();
            $initial->sendContent();
            $this->assertSame('first', ob_get_contents());
            ob_end_clean();

            $fs->delete('aafile.txt');
            $request->headers->set('If-None-Match', $initial->getEtag());
            $missing = $this->getResponse($controller, $request);
            $this->assertSame(Response::HTTP_OK, $missing->getStatusCode());
            $this->assertNull($missing->getEtag());
        } finally {
            $fs->deleteDirectory('');
        }
    }

    public function testS3UsesLongNameWithoutDownloadingForValidation(): void
    {
        $commands = array();
        $client = new S3Client(array(
            'version' => '2006-03-01',
            'region' => 'us-east-1',
            'credentials' => array('key' => 'test', 'secret' => 'test'),
            'handler' => function (CommandInterface $command) use (&$commands): FulfilledPromise {
                $commands[] = $command->getName();
                $this->assertSame('prefix/aa/aafile.png', $command['Key']);
                $metadata = array(
                    'ContentLength' => 4,
                    'ContentType' => 'image/png',
                );
                if ($command->getName() === 'GetObject') {
                    $metadata['Body'] = Utils::streamFor('blah');
                }
                return new FulfilledPromise(new Result($metadata));
            },
        ));
        $fs = new Filesystem(new AwsS3V3Adapter($client, 'test-bucket', 'prefix'));
        $controller = new DownloadController($fs, 'aafile.png');
        $initial = $this->getResponse($controller);
        $this->assertSame('"aafile.png"', $initial->getEtag());
        $this->assertNull($initial->getLastModified());
        $this->assertNotContains('GetObject', $commands);
        ob_start();
        $initial->sendContent();
        $this->assertSame('blah', ob_get_contents());
        ob_end_clean();

        $request = Request::create('/download');
        $request->headers->set('If-None-Match', $initial->getEtag());
        $response = $this->getResponse($controller, $request);
        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $response->sendContent();
        $this->assertSame(1, count(array_filter($commands, static fn(string $name): bool => $name === 'GetObject')));

    }

    public function testMissingFileDoesNotProduceNotModified(): void
    {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('fileExists')->willReturn(false);
        $fs->method('mimeType')->willReturn('text/plain');
        $fs->expects($this->never())->method('lastModified');
        $fs->expects($this->never())->method('checksum');
        $request = Request::create('/download');
        $request->headers->set('If-None-Match', '*');
        $request->headers->set('If-Modified-Since', 'Fri, 01 Jan 2100 00:00:00 GMT');
        $response = $this->getResponse(new DownloadController($fs, 'aafile.txt'), $request);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNull($response->getEtag());
        $this->assertNull($response->getLastModified());
    }

    public function testConditionalVideoRequestIsCheckedBeforeRange(): void
    {
        $this->fs->write('aa/aavideo.mp4', str_repeat('x', 1000));
        $controller = new DownloadController($this->fs, 'aavideo.mp4');
        $initial = $this->getResponse($controller);
        $request = Request::create('/download');
        $request->headers->set('If-None-Match', $initial->getEtag());
        $request->headers->set('Range', 'bytes=2000-3000');
        $response = $this->getResponse($controller, $request);
        $this->assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testVideoIfRange(): void
    {
        $this->fs->write('aa/aavideo.mp4', str_repeat('x', 1000));
        $controller = new DownloadController($this->fs, 'aavideo.mp4');
        $initial = $this->getResponse($controller);
        foreach (array($initial->getEtag(), '"changed"', 'W/' . $initial->getEtag(), 'Wed, 01 Jan 1997 00:00:00 GMT') as $ifRange) {
            $request = Request::create('/download');
            $request->headers->set('Range', 'bytes=0-9');
            $request->headers->set('If-Range', $ifRange);
            $response = $this->getResponse($controller, $request);
            $matches = $ifRange === $initial->getEtag();
            $this->assertSame($matches ? Response::HTTP_PARTIAL_CONTENT : Response::HTTP_OK, $response->getStatusCode());
            ob_start();
            $response->sendContent();
            $this->assertSame(str_repeat('x', $matches ? 10 : 1000), ob_get_contents());
            ob_end_clean();
        }
    }

    public function testVideoRangeSingleRange(): void
    {
        $longName = 'aa/aavideo.mp4';
        $this->fs->write($longName, str_repeat('x', 1000));
        $controller = new DownloadController($this->fs, $longName, 'test.mp4');

        $request = Request::create('/download');
        $request->headers->set('Range', 'bytes=0-499');

        $response = $this->getResponse($controller, $request);
        $this->assertEquals(Response::HTTP_PARTIAL_CONTENT, $response->getStatusCode());
        $this->assertEquals('bytes 0-499/1000', $response->headers->get('Content-Range'));
        $this->assertEquals('500', $response->headers->get('Content-Length'));
        $this->assertEquals('bytes', $response->headers->get('Accept-Ranges'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function testVideoRangeSuffixRange(): void
    {
        $longName = 'aa/aasuffix.mp4';
        $this->fs->write($longName, str_repeat('y', 500));
        $controller = new DownloadController($this->fs, $longName, 'suffix.mp4');

        $request = Request::create('/download');
        $request->headers->set('Range', 'bytes=-100');

        $response = $this->getResponse($controller, $request);
        $this->assertEquals(Response::HTTP_PARTIAL_CONTENT, $response->getStatusCode());
        $this->assertEquals('bytes 400-499/500', $response->headers->get('Content-Range'));
        $this->assertEquals('100', $response->headers->get('Content-Length'));
    }

    public function testVideoRangeUnsatisfiable(): void
    {
        $longName = 'aa/aaunsatisfiable.mp4';
        $this->fs->write($longName, str_repeat('z', 200));
        $controller = new DownloadController($this->fs, $longName, 'bad.mp4');

        $request = Request::create('/download');
        $request->headers->set('Range', 'bytes=300-400');

        $response = $this->getResponse($controller, $request);
        $this->assertEquals(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE, $response->getStatusCode());
        $this->assertEquals('bytes */200', $response->headers->get('Content-Range'));
    }

    public function testVideoRangeForceDownload(): void
    {
        $longName = 'aa/aaforced.mp4';
        $this->fs->write($longName, str_repeat('w', 300));
        $controller = new DownloadController($this->fs, $longName, 'forced.mp4', forceDownload: true);

        $request = Request::create('/download');
        $request->headers->set('Range', 'bytes=0-99');

        $response = $this->getResponse($controller, $request);
        $this->assertEquals(Response::HTTP_PARTIAL_CONTENT, $response->getStatusCode());
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function testVideoNoRangeHeader(): void
    {
        $longName = 'aa/aanorange.mp4';
        $this->fs->write($longName, str_repeat('v', 400));
        $controller = new DownloadController($this->fs, $longName, 'norange.mp4');

        $request = Request::create('/download');

        $response = $this->getResponse($controller, $request);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('400', $response->headers->get('Content-Length'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    private function getResponse(DownloadController $controller, ?Request $request = null): Response
    {
        // The controller closes one output buffer: give it its own, not PHPUnit's.
        ob_start();
        return $controller->getResponse($request);
    }
}

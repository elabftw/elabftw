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

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadControllerTest extends \PHPUnit\Framework\TestCase
{
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem(new InMemoryFilesystemAdapter());
    }

    public function testGetResponse(): void
    {
        $longName = 'aa/aabb.txt';
        $this->fs->write($longName, 'blah');
        $controller = new DownloadController($this->fs, $longName);
        $this->assertInstanceOf(StreamedResponse::class, $controller->getResponse());
    }

    public function testGetResponseStreamsContent(): void
    {
        $longName = 'aa/aacc.docx';
        $this->fs->write($longName, 'blah');
        $controller = new DownloadController($this->fs, $longName);
        $response = $controller->getResponse();
        ob_start();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('blah', $content);
    }

    public function testVideoRangeSingleRange(): void
    {
        $longName = 'aa/aavideo.mp4';
        $this->fs->write($longName, str_repeat('x', 1000));
        $controller = new DownloadController($this->fs, $longName, 'test.mp4');

        $request = Request::create('/download');
        $request->headers->set('Range', 'bytes=0-499');

        $response = $controller->getResponse($request);
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

        $response = $controller->getResponse($request);
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

        $response = $controller->getResponse($request);
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

        $response = $controller->getResponse($request);
        $this->assertEquals(Response::HTTP_PARTIAL_CONTENT, $response->getStatusCode());
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function testVideoNoRangeHeader(): void
    {
        $longName = 'aa/aanorange.mp4';
        $this->fs->write($longName, str_repeat('v', 400));
        $controller = new DownloadController($this->fs, $longName, 'norange.mp4');

        $request = Request::create('/download');

        $response = $controller->getResponse($request);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('400', $response->headers->get('Content-Length'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }
}

<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

use function base64_decode;
use function file_put_contents;
use function is_file;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class BrandingTest extends \PHPUnit\Framework\TestCase
{
    private Branding $Branding;

    private array $tmpFiles = array();

    protected function setUp(): void
    {
        $this->Branding = new Branding(true, 1);
        $this->Branding->populate();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $tmpFile) {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function testApiPath(): void
    {
        $this->assertEquals('api/v2/instance/branding/', $this->Branding->getApiPath());
    }

    public function testReadOne(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Branding->readOne();
    }

    public function testReadBinary(): void
    {
        $response = $this->Branding->readBinary();
        $content = $response->getContent();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertSame('max-age=3600, public', $response->headers->get('Cache-Control'));
        $this->assertIsString($content);
        $this->assertSame((string) strlen($content), $response->headers->get('Content-Length'));
    }

    public function testUpdate(): void
    {
        // valid 1×1 pixel PNG
        $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=', true);
        $this->assertIsString($data);

        $file = $this->getUploadedFile($data, 'branding.png');

        $this->assertSame(1, $this->Branding->postAction(Action::Update, array('file' => $file)));

        $response = $this->Branding->readBinary();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
        $this->assertSame((string) strlen($data), $response->headers->get('Content-Length'));
        $this->assertSame('max-age=3600, public', $response->headers->get('Cache-Control'));
        $this->assertSame($data, $response->getContent());
    }

    public function testCannotUpdateWithoutWriteAccess(): void
    {
        $Branding = new Branding(false, 1);

        $this->expectException(IllegalActionException::class);
        $Branding->postAction(Action::Update, array());
    }

    public function testInvalidAction(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Branding->postAction(Action::Create, array());
    }

    public function testInvalidBrandingIdRead(): void
    {
        $Branding = new Branding(true, 42);

        $this->expectException(ImproperActionException::class);
        $Branding->readOne();
    }

    public function testInvalidBrandingIdUpdate(): void
    {
        $Branding = new Branding(true, 42);

        $this->expectException(ImproperActionException::class);
        $Branding->postAction(Action::Update, array());
    }

    public function testMissingFile(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->Branding->postAction(Action::Update, array());
    }

    public function testUnsupportedFileType(): void
    {
        $file = $this->getUploadedFile('not an image', 'branding.txt');

        $this->expectException(ImproperActionException::class);
        $this->Branding->postAction(Action::Update, array('file' => $file));
    }

    private function getUploadedFile(string $contents, string $clientName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'elabftw-branding-test-');
        $this->assertIsString($path);
        $this->assertNotFalse(file_put_contents($path, $contents));
        $this->tmpFiles[] = $path;

        return new UploadedFile($path, $clientName, null, null, true);
    }
}

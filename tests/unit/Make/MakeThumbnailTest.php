<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use GdImage;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

use function fclose;
use function fopen;
use function getimagesizefromstring;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagefilledrectangle;
use function imagepng;
use function rewind;
use function fwrite;
use function imagejpeg;
use function pack;
use function stream_get_contents;
use function strlen;
use function substr;

class MakeThumbnailTest extends \PHPUnit\Framework\TestCase
{
    private Filesystem $Fs;

    /** @var resource */
    private $SourceFile;

    protected function setUp(): void
    {
        $this->Fs = new Filesystem(new InMemoryFilesystemAdapter());
        $this->SourceFile = $this->getPngSourceFile();
    }

    protected function tearDown(): void
    {
        fclose($this->SourceFile);
    }

    public function testSaveThumb(): void
    {
        $Maker = new MakeThumbnail('image/png', $this->SourceFile, 'aa/aabb.png', $this->Fs);
        $Maker->saveThumb();

        $thumbName = 'aa/aabb.png_th.jpg';
        $this->assertTrue($this->Fs->fileExists($thumbName));

        $imageInfo = getimagesizefromstring($this->Fs->read($thumbName));
        $this->assertIsArray($imageInfo);
        $this->assertEquals(100, $imageInfo[0]);
        $this->assertEquals('image/jpeg', $imageInfo['mime']);
    }

    public function testSaveThumbRotatesJpgFromExifOrientation(): void
    {
        $sourceFile = $this->getJpegSourceFileWithOrientation(6);
        $Maker = new MakeThumbnail('image/jpeg', $sourceFile, 'aa/aabb.jpg', $this->Fs);
        try {
            $Maker->saveThumb();
        } finally {
            fclose($sourceFile);
        }

        $imageInfo = getimagesizefromstring($this->Fs->read('aa/aabb.jpg_th.jpg'));
        $this->assertIsArray($imageInfo);
        $this->assertEquals(50, $imageInfo[0]);
        $this->assertEquals(100, $imageInfo[1]);
        $this->assertEquals('image/jpeg', $imageInfo['mime']);
    }

    /** @return resource */
    private function getPngSourceFile()
    {
        $sourceFile = fopen('php://temp', 'w+b');
        $this->assertIsResource($sourceFile);

        $image = imagecreatetruecolor(200, 100);
        $this->assertInstanceOf(GdImage::class, $image);

        $color = imagecolorallocate($image, 34, 85, 136);
        $this->assertIsInt($color);
        imagefilledrectangle($image, 0, 0, 199, 99, $color);
        imagepng($image, $sourceFile);
        imagedestroy($image);
        rewind($sourceFile);

        return $sourceFile;
    }

    /** @return resource */
    private function getJpegSourceFileWithOrientation(int $orientation)
    {
        $sourceFile = fopen('php://temp', 'w+b');
        $this->assertIsResource($sourceFile);

        $image = imagecreatetruecolor(200, 100);
        $this->assertInstanceOf(GdImage::class, $image);

        $color = imagecolorallocate($image, 34, 85, 136);
        $this->assertIsInt($color);
        imagefilledrectangle($image, 0, 0, 199, 99, $color);
        imagejpeg($image, $sourceFile);
        imagedestroy($image);
        rewind($sourceFile);

        $jpeg = stream_get_contents($sourceFile);
        fclose($sourceFile);

        $sourceFile = fopen('php://temp', 'w+b');
        $this->assertIsResource($sourceFile);
        fwrite($sourceFile, $this->addExifOrientation($jpeg, $orientation));
        rewind($sourceFile);

        return $sourceFile;
    }

    private function addExifOrientation(string $jpeg, int $orientation): string
    {
        $exif = "Exif\0\0"
            . "II*\0\x08\0\0\0"
            . "\x01\0"
            . "\x12\x01\x03\0\x01\0\0\0"
            . pack('v', $orientation) . "\0\0"
            . "\0\0\0\0";

        return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2);
    }
}

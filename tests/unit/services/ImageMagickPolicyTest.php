<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Codeception\Attribute\DataProvider;
use Imagick;
use ImagickException;
use ImagickPixel;
use PHPUnit\Framework\TestCase;

use function base64_decode;
use function dirname;
use function fclose;
use function fopen;
use function getimagesizefromstring;

/**
 * Exercise the policy installed in elabimg, rather than only checking its XML.
 */
class ImageMagickPolicyTest extends TestCase
{
    #[DataProvider('forbiddenSources')]
    public function testUnsafePseudoImagesAreRejected(string $source): void
    {
        $image = new Imagick();
        $this->expectException(ImagickException::class);
        $image->readImage($source);
    }

    /** @return array<string, array{string}> */
    public static function forbiddenSources(): array
    {
        return array(
            'label' => array('label:eLabFTW'),
            'caption' => array('caption:eLabFTW'),
            'canvas' => array('xc:white'),
            'gradient' => array('gradient:black-white'),
        );
    }

    #[DataProvider('forbiddenOutputFormats')]
    public function testOnlyJpegAndPngCanBeWritten(string $format): void
    {
        $image = new Imagick();
        $image->newImage(2, 1, new ImagickPixel('white'));

        $this->expectException(ImagickException::class);
        $image->setImageFormat($format);
        $image->getImageBlob();
    }

    /** @return array<string, array{string}> */
    public static function forbiddenOutputFormats(): array
    {
        return array(
            'pdf' => array('PDF'),
            'postscript' => array('PS'),
            'svg' => array('SVG'),
            'tiff' => array('TIFF'),
            'gif' => array('GIF'),
            'miff' => array('MIFF'),
        );
    }

    #[DataProvider('oversizedDimensions')]
    public function testOversizedImagesAreRejected(int $width, int $height): void
    {
        $image = new Imagick();
        $this->expectException(ImagickException::class);
        $image->newImage($width, $height, new ImagickPixel('white'));
    }

    /** @return array<string, array{int, int}> */
    public static function oversizedDimensions(): array
    {
        return array(
            'width' => array(20000, 1),
            'height' => array(1, 20000),
        );
    }

    public function testSvgCanBeRasterizedWithoutExternalDelegates(): void
    {
        $image = new Imagick();
        $image->readImageBlob('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="10"><rect width="20" height="10" fill="red"/></svg>');
        $image->setImageFormat('png');

        $info = getimagesizefromstring($image->getImageBlob());
        $this->assertIsArray($info);
        $this->assertSame(20, $info[0]);
        $this->assertSame(10, $info[1]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function testPdfStreamsCanBeRasterizedToJpeg(): void
    {
        $source = fopen(dirname(__DIR__, 2) . '/_data/dfn.pdf', 'rb');
        $this->assertIsResource($source);
        try {
            $image = new Imagick();
            $image->readImageFile($source);
            $image->setIteratorIndex(0);
            $image->thumbnailImage(100, 0);
            $image->setImageFormat('jpeg');

            $info = getimagesizefromstring($image->getImageBlob());
            $this->assertIsArray($info);
            $this->assertSame(100, $info[0]);
            $this->assertSame('image/jpeg', $info['mime']);
        } finally {
            fclose($source);
        }
    }

    public function testTiffCanBeRasterizedToJpeg(): void
    {
        // A two-pixel RGB TIFF, created independently of the restricted encoder.
        $tiff = base64_decode('SUkqABYAAAB4nFMK7VAK7QAABjMB/woAAAEDAAEAAAACAAAAAQEDAAEAAAABAAAAAgEDAAMAAACUAAAAAwEDAAEAAAAIAAAABgEDAAEAAAACAAAAEQEEAAEAAAAIAAAAFQEDAAEAAAADAAAAFgEDAAEAAAABAAAAFwEEAAEAAAAOAAAAHAEDAAEAAAABAAAAAAAAAAgACAAIAA==', true);
        $image = new Imagick();
        $image->readImageBlob((string) $tiff);
        $image->setImageFormat('jpeg');

        $info = getimagesizefromstring($image->getImageBlob());
        $this->assertIsArray($info);
        $this->assertSame(2, $info[0]);
        $this->assertSame(1, $info[1]);
        $this->assertSame('image/jpeg', $info['mime']);
    }
}

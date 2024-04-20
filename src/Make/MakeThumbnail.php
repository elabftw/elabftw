<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\Extensions;
use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\MakeThumbnailInterface;
use Imagick;
use League\Flysystem\Filesystem;

use function exif_read_data;
use function in_array;
use function strtolower;

/**
 * Create a thumbnail from a file
 * Note: this shouldn't be needed with psalm running inside the container!
 * @psalm-suppress UndefinedClass
 */
class MakeThumbnail implements MakeThumbnailInterface
{
    private const int THUMB_WIDTH = 100;

    public function __construct(private string $mime, protected string $filePath, private string $longName, private Filesystem $storageFs) {}

    public function saveThumb(): void
    {
        $this->storageFs->write($this->getThumbFilename(), $this->getThumb());
    }

    private function getThumbFilename(): string
    {
        return $this->longName . '_th.jpg';
    }

    /**
     * Create a jpg thumbnail from images of type jpeg, png, gif, tiff, eps and pdf.
     */
    private function getThumb(): string
    {
        $image = new Imagick();
        $image->readImage($this->filePath);
        $image->setBackgroundColor('white');
        // fix pdf with black background and png
        if ($this->mime === 'application/pdf' || $this->mime === 'application/postscript' || $this->mime === 'image/png') {
            $image->setResolution(300, 300);
            $image->setImageFormat('jpg');
            $image->scaleImage(500, 500, true);
            $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }
        // create thumbnail of width 100px; height is calculated automatically to keep the aspect ratio
        $image->thumbnailImage(self::THUMB_WIDTH, 0);
        // set the thumbnail quality to 85% (default is 75%)
        $image->setCompressionQuality(85);
        // check if we need to rotate the image based on the orientation in exif of original file
        $angle = $this->getRotationAngle();
        if ($angle !== 0) {
            $image->rotateImage('#000', $angle);
        }
        // make sure to set it as jpg (a pdf will stay a pdf otherwise)
        $image->setImageFormat('jpg');
        return $image->getImageBlob();
    }

    private function getRotationAngle(): int
    {
        // if the image has exif with rotation data, read it so the thumbnail can have a correct orientation
        // only the thumbnail is rotated, the original image stays untouched
        $ext = Tools::getExt($this->longName);
        if (in_array(strtolower($ext), Extensions::HAS_EXIF, true)
            && $this->mime === 'image/jpeg') {
            $exifData = exif_read_data($this->filePath);
            if ($exifData !== false) {
                return $this->readOrientationFromExif($exifData);
            }
        }
        return 0;
    }

    /**
     * Get the rotation angle from exif data
     *
     * @param array<string, mixed> $exifData
     */
    private function readOrientationFromExif(array $exifData): int
    {
        if (empty($exifData['Orientation'])) {
            return 0;
        }
        return match ($exifData['Orientation']) {
            1 => 0,
            3 => 180,
            6 => 90,
            8 => -90,
            default => 0,
        };
    }
}

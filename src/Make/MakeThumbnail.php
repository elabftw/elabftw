<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\Extensions;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\Tools;
use function exif_read_data;
use Imagick;
use function in_array;

use Mpdf\Mpdf;
use function strtolower;

/**
 * Create a thumbnail from a file
 * Note: this shouldn't be needed with psalm running inside the container!
 * @psalm-suppress UndefinedClass
 */
final class MakeThumbnail
{
    /** @var int WIDTH the width for the thumbnail */
    private const WIDTH = 100;

    /**
     * Do some sane white-listing. In theory, gmagick handles almost all image formats,
     * but the processing of rarely used formats may be less tested/stable or may have security issues
     * when adding new mime types take care of ambiguities:
     * e.g. image/eps may be a valid application/postscript; image/bmp may also be image/x-bmp or
     * image/x-ms-bmp
     * @var array ALLOWED_MIMES
     */
    private const ALLOWED_MIMES = array(
        'image/heic',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/tiff',
        'image/x-eps',
        'image/svg+xml',
        'application/pdf',
        'application/postscript',
    );

    public string $thumbFilename;

    public function __construct(private string $mime, private string $content, private string $longName)
    {
        $this->thumbFilename = $this->longName . '_th.jpg';
    }

    /**
     * Create a jpg thumbnail from images of type jpeg, png, gif, tiff, eps and pdf.
     */
    public function makeThumb(): ?string
    {
        // verify mime type
        if (!in_array($this->mime, self::ALLOWED_MIMES, true)) {
            return null;
        }
        if ($this->mime === 'application/pdf') {
            // Don't try to process pdf produced by iTextSharp, as they will fail for some unknown reason
            if (str_contains($this->content, 'iTextSharp')) {
                return null;
            }
            // we're going to use Mpdf to load only the first page, so we don't give all the pages to imagick
            // build a mpdf object with just the tmp dir and wrappers settings
            $mpdf = new Mpdf(array(
                'tempDir' => FsTools::getCacheFolder('mpdf'),
                // disallow getting external things
                'whitelistStreamWrappers' => array(''),
            ));

            // we don't want to have to write to disk the content so use memory instead
            $fileHandle = fopen('php://temp', 'r+');
            if ($fileHandle === false) {
                return null;
            }
            $res = fwrite($fileHandle, $this->content);
            if ($res === false) {
                return null;
            }
            rewind($fileHandle);
            // Set the source file for mPDF
            $mpdf->setSourceFile($fileHandle);

            // Import the first page
            $mpdf->AddPage();
            $tplId = $mpdf->ImportPage(1);
            $mpdf->UseTemplate($tplId);

            // Output the first page as a new PDF string
            $this->content = $mpdf->Output('', 'S');
        }

        return $this->useImagick();
    }

    private function useImagick(): string
    {
        $image = new Imagick();
        $image->setBackgroundColor('white');
        $image->readImageBlob($this->content);
        // fix pdf with black background and png
        if ($this->mime === 'application/pdf' || $this->mime === 'application/postscript' || $this->mime === 'image/png') {
            $image->setResolution(300, 300);
            $image->setImageFormat('jpg');
            $image->scaleImage(500, 500, true);
            $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        }
        // create thumbnail of width 100px; height is calculated automatically to keep the aspect ratio
        $image->thumbnailImage(self::WIDTH, 0);
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
            // create a stream from the file content so exif_read_data can read it
            $stream = fopen(sprintf('data://text/plain;base64,%s', base64_encode($this->content)), 'rb');
            if ($stream === false) {
                return 0;
            }
            $exifData = exif_read_data($stream);
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
        switch ($exifData['Orientation']) {
            case 1:
                return 0;
            case 3:
                return 180;
            case 6:
                return 90;
            case 8:
                return -90;
            default:
                return 0;
        }
    }
}

<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Exception;
use function extension_loaded;
use function filesize;
use Gmagick;
use function is_readable;

/**
 * Create a thumbnail from a file
 */
final class MakeThumbnail
{
    /** @var int BIG_FILE_THRESHOLD size of a file in bytes above which we don't process it (5 Mb) */
    private const BIG_FILE_THRESHOLD = 5000000;

    /** @var int WIDTH the width for the thumbnail */
    private const WIDTH = 100;

    /**
     * Do some sane white-listing. In theory, gmagick handles almost all image formats,
     * but the processing of rarely used formats may be less tested/stable or may have security issues
     * when adding new mime types take care of ambiguities:
     * e.g. image/eps may be a valid application/postscript; image/bmp may also be image/x-bmp or
     * image/x-ms-bmp
     * @var array GMAGICK_WHITELIST
     */
    private const GMAGICK_WHITELIST = array(
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/tiff',
        'image/x-eps',
        'image/svg+xml',
        'application/pdf',
        'application/postscript',
    );

    /** @var string $filePath full path to file */
    private $filePath;

    /** @var string $thumbPath full path to thumbnail */
    private $thumbPath;

    /** @var string $mime mime type of the file */
    private $mime;

    /**
     * This class has no public method. Just instance it with a filePath and it creates the thumbnail if needed.
     *
     * @param string $filePath the full path to the file
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        // get mime type of the file
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($this->filePath);
        if ($mime === false) {
            throw new ImproperActionException('Cannot detect the file type for thumbnail!');
        }
        $this->mime = $mime;
        $this->thumbPath = $this->filePath . '_th.jpg';
    }

    /**
     * Create a jpg thumbnail from images of type jpeg, png, gif, tiff, eps and pdf.
     *
     * @param bool $force force regeneration of thumbnail even if file exist (useful if upload was replaced)
     * @return void
     */
    public function makeThumb($force = false): void
    {
        if (is_readable($this->filePath) === false) {
            throw new FilesystemErrorException('File not found! (' . \substr($this->filePath, 0, 42) . '…)');
        }

        // do nothing for big files
        if (filesize($this->filePath) > self::BIG_FILE_THRESHOLD) {
            return;
        }

        // don't bother if the thumbnail exists already
        if (\file_exists($this->thumbPath) && $force === false) {
            return;
        }

        // use gmagick preferentially
        // FIXME at the moment there is a bug with only png files on thumbnail generation, so use GD for png
        if (extension_loaded('gmagick') && Tools::getExt($this->filePath) !== 'png') {
            $this->useGmagick();

        // if we don't have gmagick, try with gd
        } elseif (extension_loaded('gd')) {
            $this->useGd();
        }
    }

    /**
     * Create a thumbnail with Gmagick extension
     *
     * @return void
     */
    private function useGmagick(): void
    {
        if (!\in_array($this->mime, self::GMAGICK_WHITELIST, true)) {
            return;
        }

        // if pdf or postscript, generate thumbnail using the first page (index 0) do the same for postscript files
        // sometimes eps images will be identified as application/postscript as well, but thumbnail generation still
        // works in those cases
        if ($this->mime === 'application/pdf' || $this->mime === 'application/postscript') {
            $this->filePath .= '[0]';
        }
        // fail silently if thumbnail generation does not work to keep file upload field functional
        // originally introduced due to issue #415.
        try {
            $image = new Gmagick($this->filePath);
        } catch (Exception $e) {
            return;
        }

        // create thumbnail of width 100px; height is calculated automatically to keep the aspect ratio
        $image->thumbnailimage(self::WIDTH, 0);
        // create the physical thumbnail image to its destination (85% quality)
        $image->setCompressionQuality(85);
        $image->write($this->thumbPath);
        $image->clear();
    }

    /**
     * Create a thumbnail with GD extension
     *
     * @return void
     */
    private function useGd(): void
    {
        // the fonction used is different depending on extension
        switch ($this->mime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($this->filePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($this->filePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($this->filePath);
                break;
            default:
                return;
        }

        // something went wrong
        if ($sourceImage === false) {
            return;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        // find the "desired height" of this thumbnail, relative to the desired width
        $desiredHeight = (int) floor((float) $height * ((float) self::WIDTH / (float) $width));

        // create a new, "virtual" image
        $virtualImage = imagecreatetruecolor(self::WIDTH, $desiredHeight);
        if ($virtualImage === false) {
            return;
        }

        // copy source image at a resized size
        imagecopyresized($virtualImage, $sourceImage, 0, 0, 0, 0, self::WIDTH, $desiredHeight, $width, $height);

        // create the physical thumbnail image to its destination (85% quality)
        imagejpeg($virtualImage, $this->thumbPath, 85);
    }
}

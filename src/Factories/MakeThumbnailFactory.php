<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Factories;

use Elabftw\Interfaces\MakeThumbnailInterface;
use Elabftw\Make\MakeNullThumbnail;
use Elabftw\Make\MakeThumbnail;
use Elabftw\Make\MakeThumbnailFromFirstFrame;
use League\Flysystem\Filesystem;

/**
 * Get a thumbnail maker depending on the mime type
 */
final class MakeThumbnailFactory
{
    /**
     * Do some sane white-listing. In theory, gmagick handles almost all image formats,
     * but the processing of rarely used formats may be less tested/stable or may have security issues
     * when adding new mime types take care of ambiguities:
     * e.g. image/eps may be a valid application/postscript; image/bmp may also be image/x-bmp or
     * image/x-ms-bmp
     */
    public static function getMaker(string $mime, string $filePath, string $longName, Filesystem $storageFs): MakeThumbnailInterface
    {
        return match ($mime) {
            'application/pdf',
            'application/postscript',
            'image/gif',
            'image/heic',
            'image/heif',
            'image/tiff',
            'image/x-eps' => new MakeThumbnailFromFirstFrame($mime, $filePath, $longName, $storageFs),
            'image/jpeg',
            'image/png',
            'image/svg+xml' => new MakeThumbnail($mime, $filePath, $longName, $storageFs),
            default => new MakeNullThumbnail($mime, $filePath, $longName, $storageFs),
        };
    }
}

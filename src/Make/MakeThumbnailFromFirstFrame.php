<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use League\Flysystem\Filesystem;

/**
 * Create a thumbnail from an image format with potentially multiple frames/pages.
 * e.g. PDF, eps, tiff, gif, heic, postscript
 */
final class MakeThumbnailFromFirstFrame extends MakeThumbnail
{
    public function __construct(string $mime, string $filePath, string $longName, Filesystem $storageFs)
    {
        parent::__construct($mime, $filePath, $longName, $storageFs);
        // Overwrite filePath: use [0] at the end of the file path to load only the first page/frame of the file into imagick
        $this->filePath = $filePath . '[0]';
    }
}

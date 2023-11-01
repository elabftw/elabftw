<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use League\Flysystem\Filesystem;

/**
 * Create a thumbnail from a PDF
 */
final class MakeThumbnailFromPdf extends MakeThumbnail
{
    public function __construct(string $mime, string $filePath, string $longName, Filesystem $storageFs)
    {
        parent::__construct($mime, $filePath, $longName, $storageFs);
        // Overwrite filePath: use [0] at the end of the file path to load only the first page of the pdf into imagick
        $this->filePath = $filePath . '[0]';
    }
}

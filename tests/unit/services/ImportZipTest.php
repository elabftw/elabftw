<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Users;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use const UPLOAD_ERR_OK;

class ImportZipTest extends \PHPUnit\Framework\TestCase
{
    public function testImportExperiments(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable.zip',
            'importable.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportZip(
            new Users(1, 1),
            1,
            'team',
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(2, $Import->inserted);
    }

    public function testImportItems(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable.items.zip',
            'importable.items.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportZip(
            new Users(1, 1),
            1,
            'team',
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(2, $Import->inserted);
    }

    public function testImportNoJson(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-nojson.zip',
            'importable-nojson.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportZip(
            new Users(1, 1),
            1,
            'team',
            $uploadedFile,
        );
        $this->expectException(FileNotFoundException::class);
        $Import->import();
    }
}

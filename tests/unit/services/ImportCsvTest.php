<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use const UPLOAD_ERR_OK;

class ImportCsvTest extends \PHPUnit\Framework\TestCase
{
    public function testImport(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable.csv',
            'importable.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportCsv(
            new Users(1, 1),
            1,
            ',',
            'team',
            'team',
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(3, $Import->inserted);
    }

    // import a tab delimited csv file
    public function testImportWithTab(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-tab.csv',
            'importable-tab.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportCsv(
            new Users(1, 1),
            1,
            'tab',
            'team',
            'team',
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(3, $Import->inserted);
    }

    public function testImportNoTitle(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/no-title.csv',
            'no-title.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportCsv(
            new Users(1, 1),
            1,
            ',',
            'team',
            'team',
            $uploadedFile,
        );
        $this->expectException(ImproperActionException::class);
        $Import->import();
    }

    public function testImportBadDelimiter(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-tab.csv',
            'tab.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new ImportCsv(
            new Users(1, 1),
            1,
            ',',
            'team',
            'team',
            $uploadedFile,
        );
        $this->expectException(ImproperActionException::class);
        $Import->import();
    }
}

<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Enums\BasePermissions;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use const UPLOAD_ERR_OK;

class CsvTest extends \PHPUnit\Framework\TestCase
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

        $Import = new Csv(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(3, $Import->getInserted());
    }

    public function testImportToExperiments(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable.csv',
            'importable.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Csv(
            new Users(1, 1),
            'experiments:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(3, $Import->getInserted());
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

        $Import = new Csv(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
        );
        $Import->import();
        $this->assertEquals(3, $Import->getInserted());
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

        $Import = new Csv(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
        );
        $this->expectException(ImproperActionException::class);
        $Import->import();
    }
}

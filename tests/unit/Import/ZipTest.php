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
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_OK;

class ZipTest extends \PHPUnit\Framework\TestCase
{
    private FilesystemOperator $fs;

    protected function setUp(): void
    {
        // can't use InMemory adapter here because of ziparchive
        $this->fs = new Filesystem(new LocalFilesystemAdapter(dirname(__DIR__, 3) . '/cache/elab/'));
    }

    public function testFailUploadedFile(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable.zip',
            'importable.zip',
            null,
            UPLOAD_ERR_INI_SIZE, // file is too big!
            true,
        );

        $this->expectException(ImproperActionException::class);
        new Zip(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
            $this->fs,
        );
    }

    public function testWrongMime(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/universign.asn1',
            'importable.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $this->expectException(ImproperActionException::class);
        new Zip(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
            $this->fs,
        );
    }

    public function testImportExperiments(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable.zip',
            'importable.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Zip(
            new Users(1, 1),
            'experiments:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
            $this->fs,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
    }

    public function testImportExperimentsMulti(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-multi.zip',
            'importable-multi.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Zip(
            new Users(1, 1),
            'experiments:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
            $this->fs,
        );
        $Import->import();
        $this->assertEquals(2, $Import->getInserted());
    }

    public function testImportItems(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-item.zip',
            'importable.items.zip',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        $Import = new Zip(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
            $this->fs,
        );
        $Import->import();
        $this->assertEquals(1, $Import->getInserted());
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

        $Import = new Zip(
            new Users(1, 1),
            'items:1',
            BasePermissions::Team->toJson(),
            BasePermissions::Team->toJson(),
            $uploadedFile,
            $this->fs,
        );
        $this->expectException(ImproperActionException::class);
        $Import->import();
    }
}

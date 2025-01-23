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
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Params\DisplayParams;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;

use const UPLOAD_ERR_OK;

class CsvTest extends \PHPUnit\Framework\TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

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
            BasePermissions::Team->toJson(),
            BasePermissions::User->toJson(),
            $uploadedFile,
            $this->logger,
            EntityType::Items,
            category: 1,
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
            BasePermissions::Team->toJson(),
            BasePermissions::User->toJson(),
            $uploadedFile,
            $this->logger,
            EntityType::Experiments,
            category: 1,
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
            BasePermissions::Team->toJson(),
            BasePermissions::User->toJson(),
            $uploadedFile,
            $this->logger,
            EntityType::Items,
            category: 1,
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
            BasePermissions::Team->toJson(),
            BasePermissions::User->toJson(),
            $uploadedFile,
            $this->logger,
            EntityType::Items,
            category: 1,
        );
        $this->expectException(ImproperActionException::class);
        $Import->import();
    }

    // import a file not produced by elabftw
    public function testImportCustom(): void
    {
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/importable-chem.csv',
            'importable-chem.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );

        // use titi
        $requester = new Users(2, 1);
        $canread = BasePermissions::Organization;
        $canwrite = BasePermissions::Team;
        $category = 1;
        $Import = new Csv(
            $requester,
            $canread->toJson(),
            $canwrite->toJson(),
            $uploadedFile,
            $this->logger,
            EntityType::Items,
            category: $category,
        );
        $Import->import();
        $this->assertEquals(13, $Import->getInserted());
        $Items = new Items($requester);
        // filter on our user
        $query = new InputBag(array('owner' => $requester->userid));
        $last = $Items->readAll(new DisplayParams($requester, EntityType::Items, $query))[0];
        $this->assertEquals($requester->userid, $last['userid']);
        $this->assertEquals('Nitric Acid', $last['title']);
        // only look at base because the order of keys is not guaranteed
        $this->assertEquals($canread->value, json_decode($last['canread'], true, 3)['base']);
        $this->assertEquals($canwrite->value, json_decode($last['canwrite'], true, 3)['base']);
        $this->assertEquals($category, $last['category']);
    }
}

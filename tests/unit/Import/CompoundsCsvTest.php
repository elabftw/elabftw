<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Import;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\Storage;
use Elabftw\Models\Compounds;
use Elabftw\Models\Items;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function dirname;
use function file_put_contents;
use function sprintf;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

use const UPLOAD_ERR_OK;

class CompoundsCsvTest extends \PHPUnit\Framework\TestCase
{
    public function testImport(): void
    {
        $requester = new Users(1, 1);
        $Items = new Items($requester);
        $uploadedFile = new UploadedFile(
            dirname(__DIR__, 2) . '/_data/compounds.csv',
            'compounds.csv',
            null,
            UPLOAD_ERR_OK,
            true,
        );
        $fixturesFs = Storage::FIXTURES->getStorage()->getFs();
        $cidJson = $fixturesFs->read('cid-3345.json');
        $mock = new MockHandler(array(
            new Response(200, array(), $cidJson),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client, '', false);
        $Compounds = new Compounds($httpGetter, $requester, new NullFingerprinter(), false);
        $cid = 3345;
        $compoundId = $Compounds->postAction(Action::Duplicate, array('cid' => $cid));
        $Compounds->setId($compoundId);
        $Import = new CompoundsCsv(new NullLogger(), $Items, $uploadedFile, $Compounds, 1);
        $this->assertTrue($Import->import() > 1);
    }

    public function testImportUsesFullStoragePathWhenChildNamesAreDuplicated(): void
    {
        $requester = new Users(1, 1);
        $Items = new Items($requester);
        $StorageUnits = new StorageUnits($requester, requireEditRights: false);

        $suffix = uniqid('', true);
        $lab1 = sprintf('CSV duplicate lab 1 %s', $suffix);
        $lab2 = sprintf('CSV duplicate lab 2 %s', $suffix);
        $freezer = sprintf('CSV duplicate freezer %s', $suffix);

        $lab1Id = $StorageUnits->create($lab1);
        $StorageUnits->create($freezer, $lab1Id);

        $lab2Id = $StorageUnits->create($lab2);
        $targetFreezerId = $StorageUnits->create($freezer, $lab2Id);

        $csv = sprintf(
            "name,location,quantity,unit\nRegression compound,%s/%s,42,g\n",
            $lab2,
            $freezer,
        );

        $csvPath = tempnam(sys_get_temp_dir(), 'compounds-storage-path-');
        file_put_contents($csvPath, $csv);

        try {
            $uploadedFile = new UploadedFile(
                $csvPath,
                'compounds-storage-path.csv',
                'text/csv',
                UPLOAD_ERR_OK,
                true,
            );

            $httpGetter = new HttpGetter(new Client(), '', false);
            $Compounds = new Compounds($httpGetter, $requester, new NullFingerprinter(), false);
            $Import = new CompoundsCsv(new NullLogger(), $Items, $uploadedFile, $Compounds, 1);

            $this->assertSame(1, $Import->import());
            $this->assertSame($targetFreezerId, $this->latestImportedStorageId());
        } finally {
            unlink($csvPath);
        }
    }

    private function latestImportedStorageId(): int
    {
        $Db = Db::getConnection();
        $req = $Db->prepare('SELECT storage_id FROM containers2items ORDER BY id DESC LIMIT 1');
        $Db->execute($req);

        return (int) $req->fetchColumn();
    }
}

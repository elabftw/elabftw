<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\CreateUpload;
use Elabftw\Elabftw\Db;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use ZipStream\ZipStream;

class MakeBackupZipTest extends \PHPUnit\Framework\TestCase
{
    private MakeBackupZip $MakeExp;

    private MakeBackupZip $MakeDb;

    protected function setUp(): void
    {
        // we will export things changed in this time period
        $period = '19991231-20000102';
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $this->MakeExp = new MakeBackupZip($Zip, new Experiments($Users), $period);
        $this->MakeDb = new MakeBackupZip($Zip, new Items($Users), $period);
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('export.elabftw.zip', $this->MakeExp->getFileName());
    }

    public function testGetZipExp(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $id = $Experiments->create();
        // there are no public method to set last change, so do it manually
        // it has to be in the past because it's a timestamp
        $Db = Db::getConnection();
        $sql = 'UPDATE experiments SET modified_at = "2000-01-01 12:00:00" WHERE id = ' . $id;
        $req = $Db->prepare($sql);
        $req->execute();
        // add a file to it
        $Experiments->setId($id);
        $Experiments->Uploads->create(new CreateUpload('a.txt', __FILE__));
        $this->MakeExp->getStreamZip();
    }

    public function testGetZipDb(): void
    {
        $this->MakeDb->getStreamZip();
    }
}

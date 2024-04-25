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
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use ZipStream\ZipStream;

class MakeStreamZipTest extends \PHPUnit\Framework\TestCase
{
    private MakeStreamZip $MakeExp;

    private MakeStreamZip $MakeDb;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $this->MakeExp = new MakeStreamZip($Zip, new Experiments($Users), $idArr);
        $this->MakeDb = new MakeStreamZip($Zip, new Items($Users), $idArr);
    }

    public function testGetFileName(): void
    {
        $this->assertEquals('export.elabftw.zip', $this->MakeExp->getFileName());
    }

    public function testGetZipExp(): void
    {
        $this->MakeExp->getStreamZip();
    }

    public function testGetZipOneExp(): void
    {
        $Experiments = new Experiments(new Users(1, 1), 1);
        // add two files with the same name to that experiment
        $filepath = dirname(__DIR__, 2) . '/_data/example.png';
        $filename = 'similar';
        $Experiments->Uploads->create(new CreateUpload($filename, $filepath));
        $Experiments->Uploads->create(new CreateUpload($filename, $filepath));
        $Zip = $this->createMock(ZipStream::class);
        $MakeExp = new MakeStreamZip($Zip, $Experiments, array('1'));
        $MakeExp->getStreamZip();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} - .*.zip$/', $MakeExp->getFileName());
    }

    public function testGetZipDb(): void
    {
        $this->MakeDb->getStreamZip();
    }
}

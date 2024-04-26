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

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use ZipStream\ZipStream;

class MakeElnTest extends \PHPUnit\Framework\TestCase
{
    private MakeEln $MakeExp;

    private MakeEln $MakeDb;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $Users = new Users(1, 1);
        $Zip = $this->createMock(ZipStream::class);
        $this->MakeExp = new MakeEln($Zip, new Experiments($Users), $idArr);
        $this->MakeDb = new MakeEln($Zip, new Items($Users), $idArr);
    }

    public function testGetFileName(): void
    {
        $this->assertStringEndsWith('-export.eln', $this->MakeExp->getFileName());
    }

    public function testGetElnExp(): void
    {
        $this->MakeExp->getStreamZip();
    }

    public function testGetZipDb(): void
    {
        $this->MakeDb->getStreamZip();
    }
}

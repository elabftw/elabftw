<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    private MakeCsv $MakeExp;

    private MakeCsv $MakeDb;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $this->MakeExp = new MakeCsv(new Experiments(new Users(1, 1)), $idArr);
        $this->MakeDb = new MakeCsv(new Items(new Users(1, 1)), $idArr);
    }

    public function testGetFileName(): void
    {
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-export.elabftw.csv/', $this->MakeExp->getFileName());
    }

    public function testGetCsvExp(): void
    {
        $this->assertIsString($this->MakeExp->getFileContent());
    }

    public function testGetCsvDb(): void
    {
        $this->assertIsString($this->MakeDb->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('text/csv; charset=UTF-8', $this->MakeDb->getContentType());
    }
}

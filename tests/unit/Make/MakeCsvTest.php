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

use Elabftw\Enums\EntityType;
use Elabftw\Models\Users;

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    private MakeCsv $MakeExp;

    private MakeCsv $MakeDb;

    protected function setUp(): void
    {
        $idArr = array('1', '2', '3');
        $requester = new Users(1, 1);
        $this->MakeExp = new MakeCsv($requester, EntityType::Experiments, $idArr);
        $this->MakeDb = new MakeCsv($requester, EntityType::Items, $idArr);
    }

    public function testGetFileName(): void
    {
        $this->assertTrue(str_ends_with($this->MakeExp->getFileName(), 'export.elabftw.csv'));
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

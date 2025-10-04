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

use Elabftw\Enums\ReportScopes;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\Response;

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeCsv $Make;

    protected function setUp(): void
    {
        $this->Make = new MakeCsv(
            array($this->getFreshExperiment(), $this->getFreshExperiment())
        );
    }

    public function testGetFileName(): void
    {
        $this->assertTrue(str_ends_with($this->Make->getFileName(), 'export.elabftw.csv'));
    }

    public function testGetCsv(): void
    {
        $this->assertIsString($this->Make->getFileContent());
    }

    public function testGetContentType(): void
    {
        $this->assertEquals('text/csv; charset=UTF-8', $this->Make->getContentType());
    }

    public function testMakeProcurementRequestCsv(): void
    {
        // TODO rewrite the class so it can properly be tested
        $ProcurementRequests = new ProcurementRequests(new Teams(new Users(1, 1)), 1);
        // nothing to export!
        $this->expectException(ImproperActionException::class);
        new MakeProcurementRequestsCsv($ProcurementRequests);
    }

    public function testMakeInventoryReport(): void
    {
        $Maker = new MakeInventoryReport(new StorageUnits(new Users(1, 1), false));
        $this->assertIsString($Maker->getFileContent());
    }

    public function testMakeStoredCompoundsReport(): void
    {
        $Maker = new MakeStoredCompoundsReport(new StorageUnits(new Users(1, 1), false));
        $this->assertIsString($Maker->getFileContent());
    }

    public function testMakeTeamReport(): void
    {
        $Maker = new MakeTeamReport(new Users(1, 1));
        $this->assertIsString($Maker->getFileContent());
        // with non admin user
        $this->expectException(IllegalActionException::class);
        new MakeTeamReport(new Users(2, 1));
    }

    public function testMakeInstanceReport(): void
    {
        // with non sysadmin user
        $this->expectException(IllegalActionException::class);
        new MakeReport(new Users(2, 1));
    }

    public function testReportsHandler(): void
    {
        $Handler = new ReportsHandler(new Users(1, 1));
        $this->assertInstanceOf(Response::class, $Handler->getResponse(ReportScopes::Compounds));
        $this->assertInstanceOf(Response::class, $Handler->getResponse(ReportScopes::Instance));
        $this->assertInstanceOf(Response::class, $Handler->getResponse(ReportScopes::Inventory));
        $this->assertInstanceOf(Response::class, $Handler->getResponse(ReportScopes::StoredCompounds));
        $this->assertInstanceOf(Response::class, $Handler->getResponse(ReportScopes::Team));

    }
}

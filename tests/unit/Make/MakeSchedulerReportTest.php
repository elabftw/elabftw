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

use DateInterval;
use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Scheduler;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

class MakeSchedulerReportTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeSchedulerReport $Make;

    protected function setUp(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Item = $this->getFreshItemWithGivenUser($user);
        $Scheduler = new Scheduler($Item);
        $d = new DateTimeImmutable('+3 hour');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $Scheduler->postAction(Action::Create, array(
            'title' => 'for test',
            'start' => $start,
            'end' => $end,
        ));
        $this->Make = new MakeSchedulerReport($Scheduler);
    }

    public function testGenerate(): void
    {
        $this->assertIsString($this->Make->getFileContent());
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}-report.elabftw.csv/', $this->Make->getFileName());
    }

    public function testMakeSchedulerReportOnEmpty(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $this->expectException(ImproperActionException::class);
        new MakeSchedulerReport($Scheduler)->getResponse();
    }

    public function testMakeSchedulerReportWithParams(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $title = 'for report';
        $start = new DateTimeImmutable('+3 hour')->format('c');
        $end = new DateTimeImmutable('+4 hour')->format('c');
        // Add our event to the current existing ones
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => $title));
        $q = $Scheduler->getQueryParams(new InputBag(array('items' => array($Items->id))));
        $Make = new MakeSchedulerReport($Scheduler, $q);
        $response = $Make->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        // csv body
        $csv = $Make->getFileContent();
        $this->assertNotEmpty($csv, 'CSV response should not be empty');
        // normalize and split lines
        $lines = array_filter(array_map('trim', explode("\n", $csv)));
        $this->assertGreaterThan(1, count($lines), 'CSV should contain header + at least one data line');
        // parse header + first data row
        $header = str_getcsv($lines[0]);
        $row = str_getcsv($lines[1]);
        // simple sanity check: header contains known columns
        $this->assertContains('title_only', $header);
        $this->assertContains('items_id', $header);
        // extract indexes to check row values
        $idxTitle = array_search('title_only', $header, true);
        $idxItem = array_search('items_id', $header, true);
        $idxUser = array_search('fullname', $header, true);
        // assertions on the row data
        $this->assertSame($title, $row[$idxTitle], 'Event title should match');
        $this->assertSame((string) $Items->id, $row[$idxItem], 'Item ID should match');
        $this->assertNotEmpty($row[$idxUser], 'User full name should be present');
    }
}

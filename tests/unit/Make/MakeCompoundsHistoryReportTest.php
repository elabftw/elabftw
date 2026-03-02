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

use DateTimeImmutable;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Compounds;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Models\Links\Compounds2ItemsLinks;
use Elabftw\Models\Links\Experiments2ItemsLinks;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;

class MakeCompoundsHistoryReportTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCsv(): void
    {
        $requester = new Users(1, 1);
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $this->expectException(ImproperActionException::class);
        new MakeCompoundsHistoryReport(
            new Compounds($httpGetter, $requester, new NullFingerprinter(), false),
            new DateTimeImmutable('10 year ago'),
            new DateTimeImmutable('now'),
        );
    }

    public function testGetCsvWithCompounds(): void
    {
        $requester = new Users(1, 1);
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $Experiments = new Experiments($requester);
        $expId = $Experiments->create(title: 'experiment with compounds', date: new DateTimeImmutable('2022-02-22'));
        $Experiments->setId($expId);
        $Compounds = new Compounds($httpGetter, $requester, new NullFingerprinter(), false);
        $compound1Id = $Compounds->create(name: 'compound1');
        $compound2Id = $Compounds->create(name: 'compound2');
        // link compound 1 directly to the experiment
        $Compounds2Experiments = new Compounds2ExperimentsLinks($Experiments, $compound1Id);
        $Compounds2Experiments->create();
        // link compound 2 to a resource that we will link to our experiment
        $Items = new Items($requester);
        $itemId = $Items->create(title: 'my resource with a compound');
        $Items->setId($itemId);
        $Compounds2Items = new Compounds2ItemsLinks($Items, $compound2Id);
        $Compounds2Items->create();
        // now link our resource to the experiment
        $Experiments2Items = new Experiments2ItemsLinks($Experiments, $itemId);
        $Experiments2Items->create();
        // create the csv and verify both compounds appear
        $report = new MakeCompoundsHistoryReport(
            $Compounds,
            new DateTimeImmutable('2022-02-21'),
            new DateTimeImmutable('2022-02-23'),
        );
        $csv = $report->getFileContent();
        $this->assertStringContainsString('compound1', $csv);
        $this->assertStringContainsString('compound2', $csv);
    }
}

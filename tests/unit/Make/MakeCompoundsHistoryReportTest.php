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
use Elabftw\Enums\Action;
use Elabftw\Enums\GhsSignalWord;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Compounds;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Links\Compounds2ExperimentsLinks;
use Elabftw\Models\Links\Compounds2ItemsLinks;
use Elabftw\Models\Links\Experiments2ItemsLinks;
use Elabftw\Models\Reactions;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use GuzzleHttp\Client;
use League\Csv\Reader;

use function array_filter;
use function array_values;
use function iterator_to_array;

class MakeCompoundsHistoryReportTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCsv(): void
    {
        $requester = new Users(1, 1);
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $this->expectException(ImproperActionException::class);
        new MakeCompoundsHistoryReport(
            new Compounds($httpGetter, $requester, new NullFingerprinter(), false),
            // a period without any experiment: the ones of other tests, with their compounds and reactions, are dated recently
            new DateTimeImmutable('1970-01-01'),
            new DateTimeImmutable('1970-01-02'),
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

    public function testGetCsvWithReactionComponents(): void
    {
        $requester = new Users(1, 1);
        $httpGetter = new HttpGetter(new Client(), verifyTls: false);
        $Experiments = new Experiments($requester);
        $Experiments->setId($Experiments->create(title: 'experiment with a reaction', date: new DateTimeImmutable('2019-07-14')));
        $Compounds = new Compounds($httpGetter, $requester, new NullFingerprinter(), false);
        $compoundId = $Compounds->create(
            name: 'reacted compound',
            isFlammable: true,
            ghsSignalWord: GhsSignalWord::Danger,
            ghsStatements: array('H225', 'P210'),
        );
        $reference = '00000000-0000-4000-8000-000000000001';
        $reactionId = new Reactions($Experiments)->postAction(Action::Create, array(
            'title' => 'Report reaction',
            'temperature_c' => 80,
            'reference_component_key' => $reference,
            'yield_basis_component_key' => $reference,
            'components' => array(
                array('component_key' => $reference, 'name' => 'Starting material', 'compound_id' => $compoundId, 'molecular_weight' => 100, 'actual_value' => 1),
                array('role' => 'product', 'name' => 'Unlinked product', 'molecular_formula' => 'C2H6O', 'molecular_weight' => 100, 'actual_value' => 0.8),
            ),
        ));

        $csv = new MakeCompoundsHistoryReport($Compounds, new DateTimeImmutable('2019-07-13'), new DateTimeImmutable('2019-07-15'))->getFileContent();
        $rows = array_values(array_filter(
            iterator_to_array(Reader::fromString($csv)->setHeaderOffset(0)->getRecords(), false),
            static fn(array $row): bool => $row['reaction_id'] === (string) $reactionId,
        ));
        $this->assertCount(2, $rows);
        [$reactant, $product] = $rows;

        $this->assertSame('reaction', $reactant['source']);
        $this->assertSame('Report reaction', $reactant['reaction_title']);
        $this->assertSame('reactant', $reactant['role']);
        $this->assertSame('1', $reactant['is_reference_component']);
        $this->assertSame((string) $compoundId, $reactant['compound_id']);
        $this->assertSame('reacted compound', $reactant['name']);
        $this->assertSame('g', $reactant['actual_unit']);
        $this->assertEqualsWithDelta(0.01, (float) $reactant['actual_amount_mol'], 1e-9);
        $this->assertSame('Danger', $reactant['ghs_signal_word']);
        $this->assertSame('H225', $reactant['ghs_hazard_codes']);
        $this->assertStringContainsString('H225: Highly flammable liquid and vapour.', $reactant['ghs_hazard_statements']);
        $this->assertSame('P210', $reactant['ghs_precautionary_codes']);
        $this->assertSame('1', $reactant['is_flammable']);

        // a component without a linked compound is still listed, with its own structure
        $this->assertSame('product', $product['role']);
        $this->assertSame('', $product['compound_id']);
        $this->assertSame('Unlinked product', $product['name']);
        $this->assertSame('C2H6O', $product['molecular_formula']);
        $this->assertEqualsWithDelta(80.0, (float) $product['yield_percent'], 1e-6);
    }
}

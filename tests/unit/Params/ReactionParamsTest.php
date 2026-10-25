<?php

declare(strict_types=1);

namespace Elabftw\Tests;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\ReactionParams;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function json_encode;

final class ReactionParamsTest extends TestCase
{
    public function testCanonicalQuantitiesAndDuplicateChemicalOccurrences(): void
    {
        $params = new ReactionParams(array('title' => 'Reaction', 'temperature_c' => '-78', 'components' => array(
            array('name' => 'First addition', 'smiles' => 'CCO', 'planned_value' => '0.000000000123', 'actual_value' => '1.23e-9'),
            array('name' => 'Second addition', 'smiles' => 'CCO', 'actual_value' => 0),
        )));
        $this->assertSame('-78', $params->reaction['temperature_c']);
        $this->assertSame('0.000000000123', $params->components[0]['planned_value']);
        $this->assertSame('0.00000000123', $params->components[0]['actual_value']);
        $this->assertSame('0', $params->components[1]['actual_value']);
        $this->assertNotSame($params->components[0]['component_key'], $params->components[1]['component_key']);
    }

    public function testRejectsMalformedAndOutOfRangeData(): void
    {
        $invalid = array(
            array('temperature_c' => -274), array('duration_s' => -1), array('preview_png' => '<svg onload="alert(1)"/>'),
            array('scheme' => array('root' => 'invalid')), array('reference_component_key' => 'missing'),
            array('components' => array(array('role' => 'other'))), array('components' => array(array('purity_percent' => 101))),
            array('components' => array(array('coefficient' => 0))), array('components' => array(array('actual_value' => 'NaN'))),
            array('components' => array(array('actual_value' => '1e999'))), array('components' => array(array('actual_value' => '1e-20'))),
            array('components' => array(array('actual_value' => array(1)))), array('components' => array(array('compound_id' => true))),
            array('components' => array(array('actual_basis' => 'equivalents'))),
        );
        foreach ($invalid as $data) {
            try {
                new ReactionParams(array_merge(array('title' => 'Invalid'), $data));
                $this->fail('Accepted invalid reaction: ' . json_encode($data));
            } catch (ImproperActionException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testRejectsReferenceInEquivalents(): void
    {
        $key = '00000000-0000-4000-8000-000000000001';
        $this->expectException(ImproperActionException::class);
        new ReactionParams(array('title' => 'Circular', 'reference_component_key' => $key,
            'components' => array(array('component_key' => $key, 'planned_basis' => 'equivalents', 'planned_value' => 1))));
    }
}

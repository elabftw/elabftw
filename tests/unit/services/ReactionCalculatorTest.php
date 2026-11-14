<?php

declare(strict_types=1);

namespace Elabftw\Tests;

use Elabftw\Params\ReactionParams;
use Elabftw\Services\ReactionCalculator;
use PHPUnit\Framework\TestCase;

final class ReactionCalculatorTest extends TestCase
{
    private const string REFERENCE = '00000000-0000-4000-8000-000000000001';

    private const string REAGENT = '00000000-0000-4000-8000-000000000002';

    private const string PRODUCT = '00000000-0000-4000-8000-000000000003';

    public function testIndependentPlannedActualQuantitiesAndYieldBasis(): void
    {
        $data = new ReactionParams(array(
            'title' => 'Synthesis', 'reference_component_key' => self::REFERENCE, 'yield_basis_component_key' => self::REAGENT,
            'components' => array(
                array('component_key' => self::REFERENCE, 'molecular_weight' => 100, 'purity_percent' => 80, 'planned_value' => 1, 'actual_value' => 0.9, 'coefficient' => 2),
                array('component_key' => self::REAGENT, 'role' => 'reagent', 'molecular_weight' => 50, 'planned_basis' => 'equivalents', 'planned_value' => 0.5, 'actual_basis' => 'amount', 'actual_value' => 0.003),
                array('component_key' => self::PRODUCT, 'role' => 'product', 'molecular_weight' => 150, 'actual_value' => 0.3, 'coefficient' => 2),
            ),
        ));
        $result = new ReactionCalculator()->calculate($data->reaction, $data->components);
        $this->assertEqualsWithDelta(0.008, $result[0]['planned']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(0.0072, $result[0]['actual']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(0.004, $result[1]['planned']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(0.2, $result[1]['planned']['mass_g'], 1e-12);
        $this->assertEqualsWithDelta(0.006, $result[2]['actual']['theoretical_amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(100 / 3, $result[2]['actual']['yield_percent'], 1e-9);
        $this->assertSame('0.9', $result[0]['actual_value']);
    }

    public function testSolutionVolumeUsesMolarityWithoutApplyingPurityTwice(): void
    {
        $data = new ReactionParams(array('title' => 'Solution', 'components' => array(array(
            'material_form' => 'solution', 'molecular_weight' => 100, 'purity_percent' => 80,
            'concentration_mol_l' => 2, 'density_g_ml' => 0.9,
            'planned_basis' => 'volume', 'planned_value' => 5, 'actual_basis' => 'mass', 'actual_value' => 4.5,
        ))));
        $result = new ReactionCalculator()->calculate($data->reaction, $data->components)[0];
        $this->assertEqualsWithDelta(0.01, $result['planned']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(4.5, $result['planned']['mass_g'], 1e-12);
        $this->assertEqualsWithDelta(0.01, $result['actual']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(5, $result['actual']['volume_ml'], 1e-12);
    }

    public function testNeatLiquidAndUnknownValues(): void
    {
        $data = new ReactionParams(array('title' => 'Neat', 'components' => array(
            array('molecular_weight' => 100, 'density_g_ml' => 0.8, 'purity_percent' => 90, 'actual_basis' => 'volume', 'actual_value' => 10),
            array('actual_value' => 0.3),
            array('role' => 'product', 'molecular_weight' => 150, 'actual_value' => 0),
            array('material_form' => 'solution', 'actual_basis' => 'mass', 'actual_value' => 1, 'molecular_weight' => 100),
        )));
        $result = new ReactionCalculator()->calculate($data->reaction, $data->components);
        $this->assertEqualsWithDelta(0.072, $result[0]['actual']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(8, $result[0]['actual']['mass_g'], 1e-12);
        $this->assertNull($result[1]['actual']['amount_mol']);
        $this->assertNull($result[2]['actual']['yield_percent']);
        $this->assertNull($result[3]['actual']['amount_mol']);
        $this->assertSame(0.0, $result[2]['actual']['amount_mol']);
    }

    public function testDisplayUnitsKeepEnteredValues(): void
    {
        $data = new ReactionParams(array('title' => 'Units', 'components' => array(
            array('molecular_weight' => 100, 'actual_basis' => 'mass', 'actual_unit' => 'mg', 'actual_value' => '25'),
            array('actual_basis' => 'amount', 'actual_unit' => 'mmol', 'actual_value' => '2'),
        )));
        $result = new ReactionCalculator()->calculate($data->reaction, $data->components);
        $this->assertSame('25', $result[0]['actual_value']);
        $this->assertEqualsWithDelta(0.025, $result[0]['actual']['mass_g'], 1e-12);
        $this->assertEqualsWithDelta(0.00025, $result[0]['actual']['amount_mol'], 1e-12);
        $this->assertEqualsWithDelta(0.002, $result[1]['actual']['amount_mol'], 1e-12);
    }

    public function testDensityDoesNotRequireMolecularWeightAndZeroMolarityIsKnown(): void
    {
        $data = new ReactionParams(array('title' => 'Density', 'components' => array(
            array('density_g_ml' => 0.9, 'actual_basis' => 'volume', 'actual_value' => 10),
            array('material_form' => 'solution', 'density_g_ml' => 0.9, 'concentration_mol_l' => 0, 'actual_basis' => 'volume', 'actual_value' => 10),
        )));
        $result = new ReactionCalculator()->calculate($data->reaction, $data->components);
        $this->assertSame(9.0, $result[0]['actual']['mass_g']);
        $this->assertNull($result[0]['actual']['amount_mol']);
        $this->assertSame(9.0, $result[1]['actual']['mass_g']);
        $this->assertSame(0.0, $result[1]['actual']['amount_mol']);
    }
}

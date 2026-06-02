<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Params;

use function implode;
use function mb_strlen;
use function range;
use function sprintf;
use function str_contains;

class TeamParamTest extends \PHPUnit\Framework\TestCase
{
    public function testCustomUnitsHappyPath(): void
    {
        $this->assertSame('Mcells, OD600', (new TeamParam('custom_units', 'Mcells, OD600'))->getContent());
    }

    public function testCustomUnitsTrimsAndDropsEmpties(): void
    {
        $this->assertSame('mL, g', (new TeamParam('custom_units', ' mL , , g '))->getContent());
    }

    public function testCustomUnitsSplitsOnNewlines(): void
    {
        $this->assertSame('mL, g', (new TeamParam('custom_units', "mL\ng"))->getContent());
    }

    public function testCustomUnitsDeduplicates(): void
    {
        $this->assertSame('mL', (new TeamParam('custom_units', 'mL, mL'))->getContent());
    }

    public function testCustomUnitsDropsTokensLongerThanTenChars(): void
    {
        // 'elevenchars' is 11 characters and must be dropped, 'ok' kept
        $this->assertSame('ok', (new TeamParam('custom_units', 'ok, elevenchars'))->getContent());
    }

    public function testCustomUnitsStripsTags(): void
    {
        $this->assertSame('mL', (new TeamParam('custom_units', '<b>mL</b>'))->getContent());
    }

    public function testCustomUnitsKeepsMultibyteUnits(): void
    {
        // μL is 2 characters (well within the 10 char limit)
        $this->assertSame('μL, µmol', (new TeamParam('custom_units', 'μL, µmol'))->getContent());
    }

    public function testCustomUnitsReturnsNullWhenEmpty(): void
    {
        $this->assertNull((new TeamParam('custom_units', ''))->getContent());
        $this->assertNull((new TeamParam('custom_units', '   '))->getContent());
        $this->assertNull((new TeamParam('custom_units', ', ,'))->getContent());
    }

    public function testCustomUnitsStaysWithinColumnSize(): void
    {
        // 30 distinct 10-character units would join to well over 255 chars
        $units = array();
        foreach (range(1, 30) as $i) {
            $units[] = sprintf('unit%06d', $i); // 'unit' + 6 digits = 10 chars
        }
        $result = (new TeamParam('custom_units', implode(',', $units)))->getContent();
        $this->assertIsString($result);
        $this->assertLessThanOrEqual(255, mb_strlen($result));
        // the first unit is kept, the tail is dropped before overflowing
        $this->assertTrue(str_contains($result, 'unit000001'));
        $this->assertFalse(str_contains($result, 'unit000030'));
    }

    public function testHiddenUnitsKeepsValidBuiltins(): void
    {
        $this->assertSame('bar,m', (new TeamParam('hidden_units', 'bar,m'))->getContent());
    }

    public function testHiddenUnitsTrims(): void
    {
        $this->assertSame('bar,m', (new TeamParam('hidden_units', 'bar, m'))->getContent());
    }

    public function testHiddenUnitsKeepsMultibyteBuiltins(): void
    {
        $this->assertSame('•,μL', (new TeamParam('hidden_units', '•,μL'))->getContent());
    }

    public function testHiddenUnitsDropsNonBuiltins(): void
    {
        // Mcells and xyz are not built-in units, only bar survives
        $this->assertSame('bar', (new TeamParam('hidden_units', 'bar,Mcells,xyz'))->getContent());
    }

    public function testHiddenUnitsDeduplicates(): void
    {
        $this->assertSame('bar', (new TeamParam('hidden_units', 'bar,bar'))->getContent());
    }

    public function testHiddenUnitsReturnsNullWhenEmpty(): void
    {
        $this->assertNull((new TeamParam('hidden_units', ''))->getContent());
        $this->assertNull((new TeamParam('hidden_units', 'Mcells,xyz'))->getContent());
    }
}

<?php

declare(strict_types=1);

namespace Elabftw\Tests\Services;

use Elabftw\Services\ReactionIdentity;
use PHPUnit\Framework\TestCase;

final class ReactionIdentityTest extends TestCase
{
    private const string PHENOL = 'ISWSIDIOOBJBQZ-UHFFFAOYSA-N';

    private const string ANISOLE = 'RDOXTESZEPMUJZ-UHFFFAOYSA-N';

    private const string METHYL_IODIDE = 'INQOMBQAUSQDDS-UHFFFAOYSA-N';

    public function testReactionSmilesGroupsRolesInComponentOrder(): void
    {
        $components = array(
            $this->component('product', 'COc1ccccc1', self::ANISOLE),
            $this->component('reactant', 'Oc1ccccc1 phenol', self::PHENOL),
            $this->component('solvent', 'CC(C)=O'),
            $this->component('reagent', 'CI', self::METHYL_IODIDE),
            // not usable in a reaction SMILES
            $this->component('catalyst', ''),
            $this->component('reagent', 'CCO>>CC=O'),
        );
        $this->assertSame('Oc1ccccc1>CC(C)=O.CI>COc1ccccc1', ReactionIdentity::smiles($components));
        $this->assertSame('>>', ReactionIdentity::smiles(array()));
    }

    public function testKeyIgnoresAgentsOrderAndRepeatedMolecules(): void
    {
        $key = ReactionIdentity::key(array(
            $this->component('reactant', 'Oc1ccccc1', self::PHENOL),
            $this->component('reagent', 'CI', self::METHYL_IODIDE),
            $this->component('product', 'COc1ccccc1', self::ANISOLE),
        ));
        $this->assertIsString($key);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key);
        $this->assertSame($key, ReactionIdentity::key(array(
            $this->component('product', 'COc1ccccc1', self::ANISOLE),
            $this->component('solvent', 'CC(C)=O', 'CSCPPACGZOOCGX-UHFFFAOYSA-N'),
            $this->component('reactant', 'Oc1ccccc1', self::PHENOL),
            $this->component('reactant', 'Oc1ccccc1', self::PHENOL),
        )));
        // the reverse reaction is another transformation
        $this->assertNotSame($key, ReactionIdentity::key(array(
            $this->component('reactant', 'COc1ccccc1', self::ANISOLE),
            $this->component('product', 'Oc1ccccc1', self::PHENOL),
        )));
    }

    public function testKeyIsUnknownWithoutAllIdentifiers(): void
    {
        $this->assertNull(ReactionIdentity::key(array(
            $this->component('reactant', 'Oc1ccccc1', self::PHENOL),
            $this->component('reactant', '*c1ccccc1'),
            $this->component('product', 'COc1ccccc1', self::ANISOLE),
        )));
        $this->assertNull(ReactionIdentity::key(array($this->component('reactant', 'Oc1ccccc1', self::PHENOL))));
    }

    private function component(string $role, string $smiles, string $inchiKey = ''): array
    {
        return array('role' => $role, 'smiles' => $smiles, 'inchi_key' => $inchiKey);
    }
}

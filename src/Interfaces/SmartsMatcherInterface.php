<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

interface SmartsMatcherInterface
{
    /**
     * @param array<array-key, string> $smiles structures to test, by any key
     * @param bool $exact only the structures with as many heavy atoms as the pattern, which must then be a SMILES: the same molecule
     * @return list<array-key> the keys of the structures containing the SMARTS pattern
     */
    public function matches(string $smarts, array $smiles, bool $exact = false): array;
}

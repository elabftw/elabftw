<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Import;

use Elabftw\Models\Compounds;

/**
 * Import a CSV into compounds
 */
class CompoundsCsv extends AbstractCsv
{
    public function import(): int
    {
        // now loop the rows and do the import
        $Compounds = new Compounds($this->requester);
        foreach ($this->reader->getRecords() as $row) {
            $Compounds->create(
                smiles: $row['smiles'] ?? null,
                name: $row['name'] ?? null,
                casNumber: $row['cas'] ?? null,
                inchi: $row['inchi'] ?? null,
                inchiKey: $row['inchikey'] ?? null,
                pubchemCid: $row['pubmedcid'] ?? null,
                molecularFormula: $row['molecularformula'] ?? null,
                iupacName: $row['iupacname'] ?? null,
            );
        }
        return $this->getCount();
    }
}

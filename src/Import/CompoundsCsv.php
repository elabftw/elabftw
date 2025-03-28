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
use Elabftw\Models\Compounds2ItemsLinks;
use Elabftw\Models\Items;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Override;

/**
 * Import a CSV into compounds
 */
final class CompoundsCsv extends AbstractCsv
{
    public function __construct(
        protected Items $Items,
        protected UploadedFile $UploadedFile,
        protected Compounds $Compounds,
        protected ?int $resourceCategory = null,
    ) {
        parent::__construct($Items->Users, $UploadedFile);
    }

    #[Override]
    public function import(): int
    {
        foreach ($this->reader->getRecords() as $row) {
            $id = $this->Compounds->create(
                smiles: $row['smiles'] ?? null,
                name: $row['name'] ?? null,
                casNumber: $row['cas'] ?? null,
                inchi: $row['inchi'] ?? null,
                inchiKey: $row['inchikey'] ?? null,
                pubchemCid: empty($row['pubchemcid']) ? null : (int) $row['pubchemcid'],
                molecularFormula: $row['molecularformula'] ?? null,
                iupacName: $row['iupacname'] ?? null,
            );
            if ($this->resourceCategory !== null) {
                $resource = $this->Items->create(template: $this->resourceCategory, title: $row['name']);
                $this->Items->setId($resource);
                $Compounds2ItemsLinks = new Compounds2ItemsLinks($this->Items, $id);
                $Compounds2ItemsLinks->create();
            }
        }
        return $this->getCount();
    }
}

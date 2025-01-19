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
use Elabftw\Models\Config;
use Elabftw\Models\Items;
use Elabftw\Models\Users;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import a CSV into compounds
 */
class CompoundsCsv extends AbstractCsv
{
    public function __construct(
        protected Users $requester,
        protected UploadedFile $UploadedFile,
        protected ?int $resourceCategory = null,
    ) {
        parent::__construct($requester, $UploadedFile);
    }

    public function import(): int
    {
        // now loop the rows and do the import
        $Config = Config::getConfig();
        $httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0');
        $Compounds = new Compounds($httpGetter, $this->requester);
        $Items = new Items($this->requester);
        foreach ($this->reader->getRecords() as $row) {
            $id = $Compounds->create(
                smiles: $row['smiles'] ?? null,
                name: $row['name'] ?? null,
                casNumber: $row['cas'] ?? null,
                inchi: $row['inchi'] ?? null,
                inchiKey: $row['inchikey'] ?? null,
                pubchemCid: $row['pubmedcid'] ?? null,
                molecularFormula: $row['molecularformula'] ?? null,
                iupacName: $row['iupacname'] ?? null,
                withFingerprint: Config::boolFromEnv('USE_FINGERPRINTER'),
            );
            if ($this->resourceCategory !== null) {
                $resource = $Items->create(template: $this->resourceCategory, title: $row['name']);
                $Items->setId($resource);
                $Compounds2ItemsLinks = new Compounds2ItemsLinks($Items, $id);
                $Compounds2ItemsLinks->create();
            }
        }
        return $this->getCount();
    }
}

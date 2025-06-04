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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Compounds;
use Elabftw\Models\Compounds2ItemsLinks;
use Elabftw\Models\Containers2ItemsLinks;
use Elabftw\Models\Items;
use Elabftw\Models\StorageUnits;
use Elabftw\Params\DisplayParams;
use Elabftw\Params\EntityParams;
use Elabftw\Services\PubChemImporter;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Console\Output\OutputInterface;
use Override;
use Symfony\Component\HttpFoundation\InputBag;

use function sprintf;

/**
 * Import a CSV into compounds
 */
final class CompoundsCsv extends AbstractCsv
{
    public function __construct(
        private OutputInterface $output,
        protected Items $Items,
        protected UploadedFile $UploadedFile,
        protected Compounds $Compounds,
        protected ?int $resourceCategory = null,
        protected ?PubChemImporter $PubChemImporter = null,
        protected string $locationSplitter = '/',
        protected ?string $matchWith = null,
    ) {
        parent::__construct($Items->Users, $UploadedFile);
    }

    #[Override]
    public function import(): int
    {
        // number of rows is stored in a var so we can decrement it when a compound is not imported
        $count = $countAll = $this->getCount();
        $this->output->writeln(sprintf('[info] Found %d rows to import', $count));

        $loopIndex = 0;
        foreach ($this->reader->getRecords() as $row) {
            // this might store the compound from pubchem
            $compound = false;
            $id = -1;
            try {
                if ($this->PubChemImporter !== null) {
                    $cid = isset($row['pubchemcid']) ? (int) $row['pubchemcid'] : null;
                    if (empty($cid) && !empty($row['cas'])) {
                        $cid = $this->PubChemImporter->getCidFromCas($row['cas']);
                    }
                    if ($cid) {
                        $compound = $this->PubChemImporter->fromPugView($cid);
                        $id = $this->Compounds->createFromCompound($compound);
                    }
                } else {
                    $id = $this->Compounds->create(
                        casNumber: $row['cas'] ?? null,
                        ecNumber: $row['ec_number'] ?? null,
                        inchi: $row['inchi'] ?? null,
                        inchiKey: $row['inchikey'] ?? null,
                        iupacName: $row['iupacname'] ?? null,
                        name: $row['name'] ?? $row['title'] ?? null,
                        chebiId: $row['chebi_id'] ?? null,
                        chemblId: $row['chembl_id'] ?? null,
                        deaNumber: $row['dea_number'] ?? null,
                        drugbankId: $row['drugbank_id'] ?? null,
                        dsstoxId: $row['dsstox_id'] ?? null,
                        hmdbId: $row['hmdb_id'] ?? null,
                        keggId: $row['kegg_id'] ?? null,
                        metabolomicsWbId: $row['metabolomics_wb_id'] ?? null,
                        molecularFormula: $row['molecularformula'] ?? null,
                        molecularWeight: isset($row['molecularweight']) ? (float) $row['molecularweight'] : 0.00,
                        nciCode: $row['nci_code'] ?? null,
                        nikkajiNumber: $row['nikkaji_number'] ?? null,
                        pharmGkbId: $row['pharmgkb_id'] ?? null,
                        pharosLigandId: $row['pharos_ligand_id'] ?? null,
                        pubchemCid: empty($row['pubchemcid']) ? null : (int) $row['pubchemcid'],
                        rxcui: $row['rxcui'] ?? null,
                        smiles: $row['smiles'] ?? null,
                        unii: $row['unii'] ?? null,
                        wikidata: $row['wikidata'] ?? null,
                        wikipedia: $row['wikipedia'] ?? null,
                        isCorrosive: (bool) ($row['is_corrosive'] ?? false),
                        isExplosive: (bool) ($row['is_explosive'] ?? false),
                        isFlammable: (bool) ($row['is_flammable'] ?? false),
                        isGasUnderPressure: (bool) ($row['is_gas_under_pressure'] ?? false),
                        isHazardous2env: (bool) ($row['is_hazardous2env'] ?? false),
                        isHazardous2health: (bool) ($row['is_hazardous2health'] ?? false),
                        isOxidising: (bool) ($row['is_oxidising'] ?? false),
                        isSeriousHealthHazard: (bool) ($row['is_serious_health_hazard'] ?? false),
                        isToxic: (bool) ($row['is_toxic'] ?? false),
                        isRadioactive: (bool) ($row['is_radioactive'] ?? false),
                        isAntibiotic: (bool) ($row['is_antibiotic'] ?? false),
                        isAntibioticPrecursor: (bool) ($row['is_antibiotic_precursor'] ?? false),
                        isDrug: (bool) ($row['is_drug'] ?? false),
                        isDrugPrecursor: (bool) ($row['is_drug_precursor'] ?? false),
                        isExplosivePrecursor: (bool) ($row['is_explosive_precursor'] ?? false),
                        isCmr: (bool) ($row['is_cmr'] ?? false),
                        isNano: (bool) ($row['is_nano'] ?? false),
                        isControlled: (bool) ($row['is_controlled'] ?? false),
                        isEd2health: (bool) ($row['is_ed2health'] ?? false),
                        isEd2env: (bool) ($row['is_ed2env'] ?? false),
                        isPbt: (bool) ($row['is_pbt'] ?? false),
                        isPmt: (bool) ($row['is_pmt'] ?? false),
                        isVpvb: (bool) ($row['is_vpvb'] ?? false),
                        isVpvm: (bool) ($row['is_vpvm'] ?? false),
                    );
                }

                // optionally create Resource
                if ($this->resourceCategory !== null) {
                    $title = $row['name'] ?? $row['iupacname'] ?? null;
                    if ($title === null && $compound) {
                        $title = $compound->name ?? $compound->iupacName;
                    }
                    $resource = $this->Items->create(template: $this->resourceCategory, title: $title ?? 'Unnamed compound');
                    $this->Items->setId($resource);
                    if (isset($row['comment'])) {
                        $this->Items->update(new EntityParams('body', $row['comment']));
                    }
                    $this->Items->update(new EntityParams('metadata', $this->collectMetadata($row)));
                    $Compounds2ItemsLinks = new Compounds2ItemsLinks($this->Items, $id);
                    $Compounds2ItemsLinks->create();
                    // process localisation
                    if (isset($row['location']) && !empty($row['location']) && !empty($this->locationSplitter)) {
                        $locationSplit = explode($this->locationSplitter, $row['location']);
                        $StorageUnits = new StorageUnits($this->requester);
                        $id = $StorageUnits->createImmutable($locationSplit);
                        $Containers2ItemsLinks = new Containers2ItemsLinks($this->Items, $id);
                        $Containers2ItemsLinks->createWithQuantity((float) ($row['quantity'] ?? 1.0), $row['unit'] ?? '•');
                    }
                }

                // optionally link with an existing Resource that we match with the extra field
                if ($this->matchWith !== null && $row[$this->matchWith]) {
                    $resource = $this->findMatch($row[$this->matchWith]);
                    if (is_array($resource)) {
                        $this->Items->setId($resource['id']);
                        $Compounds2ItemsLinks = new Compounds2ItemsLinks($this->Items, $id);
                        $Compounds2ItemsLinks->create();
                    }
                }
            } catch (ImproperActionException | RequestException $e) {
                $this->output->writeln($e->getMessage());
                // decrement the count so we can give a correct number
                --$count;
            }
            ++$loopIndex;
            $this->output->writeln(sprintf('[info] Imported %d/%d', $loopIndex, $countAll));
        }
        return $count;
    }

    /**
     * Look for a Resource where the extra field defined in matchWith has the value provided
     */
    protected function findMatch(string $value): ?array
    {
        $queryValue = sprintf('extrafield:%s:%s', $this->matchWith ?? '', $value);
        $query = new InputBag(array('q' => $queryValue, 'scope' => 3));
        $DisplayParams = new DisplayParams($this->Items->Users, $this->Items->entityType, $query);
        $results = $this->Items->readShow($DisplayParams);
        if (count($results) > 1) {
            $this->output->writeln(sprintf('[warning] Found %d matches for %s. Linking with first one found.', count($results), $value));
        }
        return $results[0] ?? null;
    }

    #[Override]
    protected function getProcessedColumns(): array
    {
        // these are the columns that are added to the compound
        return array(
            'cas',
            'ec_number',
            'inchi',
            'inchi_key',
            'iupacname',
            'name',
            'title',
            'comment',
            'chebi_id',
            'chembl_id',
            'dea_number',
            'drugbank_id',
            'dsstox_id',
            'hmdb_id',
            'kegg_id',
            'metabolomics_wb_id',
            'molecularformula',
            'molecular_weight',
            'nci_code',
            'nikkaji_number',
            'pharmgkb_id',
            'pharos_ligand_id',
            'pubchemcid',
            'rxcui',
            'smiles',
            'unii',
            'wikidata',
            'wikipedia',
            'is_corrosive',
            'is_explosive',
            'is_flammable',
            'is_gas_under_pressure',
            'is_hazardous2env',
            'is_hazardous2health',
            'is_oxidising',
            'is_serious_health_hazard',
            'is_toxic',
            'is_radioactive',
            'is_antibiotic_precursor',
            'is_drug_precursor',
            'is_explosive_precursor',
            'is_cmr',
            'is_nano',
            'is_controlled',
        );
    }
}

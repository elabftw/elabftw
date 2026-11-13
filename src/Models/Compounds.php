<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Compound;
use Elabftw\Elabftw\CompoundPermissions;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\PermissionsHelper;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\GhsSignalWord;
use Elabftw\Enums\Orderby;
use Elabftw\Enums\State;
use Elabftw\Exceptions\CompoundNotAccessibleException;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\FingerprinterInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Interfaces\SmartsMatcherInterface;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Params\CompoundParams;
use Elabftw\Services\Check;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\OpenBabelSmartsMatcher;
use Elabftw\Services\PubChemImporter;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;
use PDOStatement;
use Symfony\Component\HttpFoundation\InputBag;

use function _;
use function array_column;
use function array_filter;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_sum;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strlen;
use function trim;

/**
 * Compounds are chemical entities stored in the `compounds` SQL table
 */
final class Compounds extends AbstractRest
{
    use SetIdTrait;

    // a compound added manually is often sensitive: by default it is visible to the team, and modifiable by its owner and the Admins of the team,
    // like an experiment. Same as the column defaults in structure.sql, and as the documentation says
    public const BasePermissions DEFAULT_CANREAD_BASE = BasePermissions::Team;

    public const BasePermissions DEFAULT_CANWRITE_BASE = BasePermissions::User;

    // a compound from PubChem is public data, and can only be added once on the instance: by default everyone with an account can read and modify it
    public const BasePermissions PUBCHEM_CANREAD_BASE = BasePermissions::Organization;

    public const BasePermissions PUBCHEM_CANWRITE_BASE = BasePermissions::Organization;

    // columns with the UNIQUE constraint: identifiers of a compound
    private const array UNIQUE_KEYS = array(
        'cas_number',
        'chebi_id',
        'chembl_id',
        'dea_number',
        'drugbank_id',
        'dsstox_id',
        'ec_number',
        'hmdb_id',
        'inchi_key',
        'kegg_id',
        'metabolomics_wb_id',
        'nci_code',
        'nikkaji_number',
        'pharmgkb_id',
        'pharos_ligand_id',
        'pubchem_cid',
        'rxcui',
        'unii',
        'wikidata',
        'wikipedia',
    );

    private const string EMPTY_CAN_JSON = '{"teams": [], "teamgroups": [], "users": []}';

    // the pattern goes to Open Babel: keep it to the size of a drawn molecule
    private const int MAX_SMARTS_LENGTH = 2048;

    // every candidate structure goes through Open Babel on each search: above this, it would take too long
    private const int MAX_SMARTS_CANDIDATES = 20000;

    private const string INCHI_KEY_REGEX = '/^[A-Z]{14}-[A-Z]{10}-[A-Z]$/';

    // the components of a reaction, see ReactionParams
    private const int MAX_INCHI_KEYS = 128;

    // the text search of readAll(), on the identifiers, names, formula and GHS codes
    private const string TEXT_SEARCH_SQL = ' AND (
        entity.cas_number LIKE :query OR
        entity.ec_number LIKE :query OR
        entity.chebi_id LIKE :query OR
        entity.chembl_id LIKE :query OR
        entity.dea_number LIKE :query OR
        entity.drugbank_id LIKE :query OR
        entity.dsstox_id LIKE :query OR
        entity.hmdb_id LIKE :query OR
        entity.inchi LIKE :query OR
        entity.inchi_key LIKE :query OR
        entity.iupac_name LIKE :query OR
        entity.kegg_id LIKE :query OR
        entity.metabolomics_wb_id LIKE :query OR
        entity.molecular_formula LIKE :query OR
        entity.molecular_weight LIKE :query OR
        entity.name LIKE :query OR
        entity.nci_code LIKE :query OR
        entity.nikkaji_number LIKE :query OR
        entity.pharmgkb_id LIKE :query OR
        entity.pharos_ligand_id LIKE :query OR
        entity.pubchem_cid LIKE :query OR
        entity.rxcui LIKE :query OR
        entity.smiles LIKE :query OR
        entity.unii LIKE :query OR
        entity.wikidata LIKE :query OR
        entity.wikipedia LIKE :query OR
        EXISTS (
            SELECT 1 FROM compounds2ghs_statements AS c2gs_q
            INNER JOIN ghs_statements AS gs_q ON (gs_q.id = c2gs_q.statement_id)
            WHERE c2gs_q.compound_id = entity.id AND CONVERT(gs_q.code USING utf8mb4) LIKE :query
        )
    )';

    public function __construct(
        protected HttpGetter $httpGetter,
        public Users $requester,
        protected FingerprinterInterface $fingerprinter,
        private bool $requireEditRights = false,
        ?int $id = null,
        private SmartsMatcherInterface $smartsMatcher = new OpenBabelSmartsMatcher(),
    ) {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/compounds/';
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->canReadOrExplode();
        $queryParams ??= $this->getQueryParams();
        if (!empty($queryParams->getQuery()->get('search_pubchem_cid'))) {
            return $this->searchPubChem($queryParams->getQuery()->getInt('search_pubchem_cid'))->toArray();
        }
        if (!empty($queryParams->getQuery()->get('search_pubchem_cas'))) {
            return $this->searchPubChemCas($queryParams->getQuery()->getString('search_pubchem_cas'));
        }
        if (!empty($queryParams->getQuery()->get('search_pubchem_name'))) {
            return $this->searchPubChemName($queryParams->getQuery()->getString('search_pubchem_name'));
        }
        // every criterion is in the same query: they combine with each other, the permissions and the states before the limit
        $query = $queryParams->getQuery();
        $text = $query->getString('q');
        $where = new CompoundPermissions($this->requester)->getCanFilter('entity');
        $similarity = '';
        if ($text !== '') {
            $where .= self::TEXT_SEARCH_SQL;
        }
        // exact InChIKeys, comma separated: an index lookup, for the structures drawn in a reaction (see chem-reactions.ts)
        $inchiKeys = self::getInchiKeys($query->getString('inchi_key'));
        if ($inchiKeys !== array()) {
            // validated by getInchiKeys(): safe to inline
            $where .= sprintf(" AND entity.inchi_key IN ('%s')", implode("', '", $inchiKeys));
        }
        if (!empty($query->get('search_fp_smi'))) {
            $fp = $this->fingerprinter->calculate('smi', $query->getString('search_fp_smi'));
            // an empty fingerprint (water, ammonia…) cannot be screened: it would find every compound
            if (array_sum($fp['data']) === 0) {
                return array();
            }
            // without exact, this is an approximate substructure search: false positives are possible, not false negatives
            $where .= $query->getBoolean('exact')
                ? FingerprintTable::equalsSql($fp['data'], 'compounds_fingerprints', ignoreEmptyWords: true)
                : FingerprintTable::containsSql($fp['data'], 'compounds_fingerprints');
            $similarity = FingerprintTable::similaritySql($fp['data'], 'compounds_fingerprints');
        }
        if (!empty($query->get('search_smarts'))) {
            // the states are in getSql() for the listing, the candidates need them too
            $where .= $this->getSmartsSql(trim($query->getString('search_smarts')), $where . $queryParams->getStatesSql('entity'), $text);
        }
        $sql = $this->getSelectBeforeWhere($similarity) . ' WHERE 1=1' . $where . $this->getOrderSql($queryParams, $similarity !== '');
        $req = $this->Db->prepare($sql);
        $this->bindSearchValues($req, $text);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function getQueryParams(?InputBag $query = null): QueryParamsInterface
    {
        return new BaseQueryParams(query: $query, orderby: Orderby::Lastchange);
    }

    #[Override]
    public function readOne(): array
    {
        $this->canReadOrExplode();
        $compound = $this->readRow();
        $permissions = new CompoundPermissions($this->requester)->forCompound($compound);
        if (!$permissions->read) {
            throw new ForbiddenException();
        }
        // what the permissions modal shows, like for an entry
        $PermissionsHelper = new PermissionsHelper();
        foreach (array('canread', 'canwrite') as $can) {
            $compound[$can . '_base_human'] = BasePermissions::from($compound[$can . '_base'])->toHuman();
            $compound[$can . '_human'] = $PermissionsHelper->translate($this->requester, $compound[$can]);
        }
        $compound['has_write_access'] = (int) ($permissions->write && $this->canWrite());
        $compound['ghs_statements'] = new GhsStatements()->readForCompounds(array($compound['id']))[$compound['id']] ?? array();
        return $compound;
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->canWriteCompoundOrExplode();
        foreach ($params as $target => $content) {
            $compoundParams = new CompoundParams($target, $content);
            // deleting through the state goes through the same check as destroy()
            if ($compoundParams->getColumn() === 'state' && $compoundParams->getContent() === State::Deleted->value) {
                $this->assertNotLinkedOrExplode();
            }
            $this->update($compoundParams);
        }
        return $this->readOne();
    }

    public function update(CompoundParams $params): bool
    {
        $sql = sprintf('UPDATE compounds SET %s = :content, modified_by = :requester WHERE id = :id', $params->getColumn());
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindValue(':content', $params->getContent());
        $req->bindParam(':requester', $this->requester->userid, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->canWriteOrExplode();
        // TODO add action fromCid or fromSmiles
        // and use fingerprinter
        // Duplicate imports the compound from PubChem
        $permissions = $this->getCreatePermissions($reqBody, fromPubChem: $action === Action::Duplicate);
        return match ($action) {
            Action::Duplicate => $this->createCompoundFromIdentifier($reqBody, $permissions),
            default => $this->create(
                canreadBase: $permissions['canread_base'],
                canwriteBase: $permissions['canwrite_base'],
                canread: $permissions['canread'],
                canwrite: $permissions['canwrite'],
                name: $reqBody['name'] ?? null,
                inchi: $reqBody['inchi'] ?? null,
                inchiKey: $reqBody['inchi_key'] ?? null,
                smiles: $reqBody['smiles'] ?? null,
                molecularFormula: $reqBody['molecular_formula'] ?? null,
                casNumber: $reqBody['cas_number'] ?? null,
                iupacName: $reqBody['iupac_name'] ?? null,
                pubchemCid: $reqBody['pubchem_cid'] ?? null,
                isCorrosive: (bool) ($reqBody['is_corrosive'] ?? false),
                isSeriousHealthHazard: (bool) ($reqBody['is_serious_health_hazard'] ?? false),
                isExplosive: (bool) ($reqBody['is_explosive'] ?? false),
                isFlammable: (bool) ($reqBody['is_flammable'] ?? false),
                isGasUnderPressure: (bool) ($reqBody['is_gas_under_pressure'] ?? false),
                isHazardous2env: (bool) ($reqBody['is_hazardous2env'] ?? false),
                isHazardous2health: (bool) ($reqBody['is_hazardous2health'] ?? false),
                isOxidising: (bool) ($reqBody['is_oxidising'] ?? false),
                isToxic: (bool) ($reqBody['is_toxic'] ?? false),
                ghsSignalWord: GhsSignalWord::tryFrom((string) ($reqBody['ghs_signal_word'] ?? '')),
                ghsStatements: $this->getGhsCodes($reqBody['ghs_statements'] ?? ''),
            ),
        };
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->canWriteCompoundOrExplode();
        $this->assertNotLinkedOrExplode();
        return $this->update(new CompoundParams('state', State::Deleted->value));
    }

    public function create(
        ?string $inchi = null,
        ?string $inchiKey = null,
        ?string $name = null,
        ?string $casNumber = null,
        ?string $ecNumber = null,
        ?string $iupacName = null,
        ?string $chebiId = null,
        ?string $chemblId = null,
        ?string $deaNumber = null,
        ?string $drugbankId = null,
        ?string $dsstoxId = null,
        ?string $hmdbId = null,
        ?string $keggId = null,
        ?string $metabolomicsWbId = null,
        ?string $molecularFormula = null,
        ?float $molecularWeight = 0.00,
        ?string $nciCode = null,
        ?string $nikkajiNumber = null,
        ?string $pharmGkbId = null,
        ?string $pharosLigandId = null,
        ?int $pubchemCid = null,
        ?string $rxcui = null,
        ?string $smiles = null,
        ?string $unii = null,
        ?string $wikidata = null,
        ?string $wikipedia = null,
        bool $isCorrosive = false,
        bool $isExplosive = false,
        bool $isFlammable = false,
        bool $isGasUnderPressure = false,
        bool $isHazardous2env = false,
        bool $isHazardous2health = false,
        bool $isOxidising = false,
        bool $isSeriousHealthHazard = false,
        bool $isToxic = false,
        bool $isRadioactive = false,
        bool $isAntibiotic = false,
        bool $isAntibioticPrecursor = false,
        bool $isDrug = false,
        bool $isDrugPrecursor = false,
        bool $isExplosivePrecursor = false,
        bool $isCmr = false,
        bool $isNano = false,
        bool $isControlled = false,
        bool $isEd2health = false,
        bool $isEd2env = false,
        bool $isPbt = false,
        bool $isPmt = false,
        bool $isVpvb = false,
        bool $isVpvm = false,
        ?GhsSignalWord $ghsSignalWord = null,
        array $ghsStatements = array(),
        BasePermissions $canreadBase = self::DEFAULT_CANREAD_BASE,
        BasePermissions $canwriteBase = self::DEFAULT_CANWRITE_BASE,
        string $canread = self::EMPTY_CAN_JSON,
        string $canwrite = self::EMPTY_CAN_JSON,
    ): int {
        // the column cannot be null: a compound without a known weight gets the column default
        $molecularWeight ??= 0.00;
        $compoundData = array(
            'inchi' => $inchi,
            'inchi_key' => $inchiKey,
            'name' => $name,
            'cas_number' => $casNumber,
            'ec_number' => $ecNumber,
            'iupac_name' => $iupacName,
            'chebi_id' => $chebiId,
            'chembl_id' => $chemblId,
            'dea_number' => $deaNumber,
            'drugbank_id' => $drugbankId,
            'dsstox_id' => $dsstoxId,
            'hmdb_id' => $hmdbId,
            'kegg_id' => $keggId,
            'metabolomics_wb_id' => $metabolomicsWbId,
            'molecular_formula' => $molecularFormula,
            'molecular_weight' => $molecularWeight,
            'nci_code' => $nciCode,
            'nikkaji_number' => $nikkajiNumber,
            'pharmgkb_id' => $pharmGkbId,
            'pharos_ligand_id' => $pharosLigandId,
            'pubchem_cid' => $pubchemCid,
            'rxcui' => $rxcui,
            'smiles' => $smiles,
            'unii' => $unii,
            'wikidata' => $wikidata,
            'wikipedia' => $wikipedia,
            'is_corrosive' => $isCorrosive,
            'is_explosive' => $isExplosive,
            'is_flammable' => $isFlammable,
            'is_gas_under_pressure' => $isGasUnderPressure,
            'is_hazardous2env' => $isHazardous2env,
            'is_hazardous2health' => $isHazardous2health,
            'is_oxidising' => $isOxidising,
            'is_serious_health_hazard' => $isSeriousHealthHazard,
            'is_toxic' => $isToxic,
            'is_radioactive' => $isRadioactive,
            'is_antibiotic' => $isAntibiotic,
            'is_antibiotic_precursor' => $isAntibioticPrecursor,
            'is_drug' => $isDrug,
            'is_drug_precursor' => $isDrugPrecursor,
            'is_explosive_precursor' => $isExplosivePrecursor,
            'is_cmr' => $isCmr,
            'is_nano' => $isNano,
            'is_controlled' => $isControlled,
            'is_ed2health' => $isEd2health,
            'is_ed2env' => $isEd2env,
            'is_pbt' => $isPbt,
            'is_pmt' => $isPmt,
            'is_vpvb' => $isVpvb,
            'is_vpvm' => $isVpvm,
            'ghs_signal_word' => $ghsSignalWord?->value,
        );

        $sql = 'INSERT INTO compounds (
            created_by, modified_by, name,
            inchi, inchi_key,
            smiles, molecular_formula, molecular_weight, metabolomics_wb_id, nci_code, nikkaji_number, pharmgkb_id, pharos_ligand_id, rxcui, unii, wikidata, wikipedia, is_radioactive, is_antibiotic, is_antibiotic_precursor, is_drug, is_drug_precursor, is_explosive_precursor, is_cmr, is_nano, is_controlled,
            cas_number, ec_number, iupac_name, pubchem_cid, userid, team, chebi_id, chembl_id, dea_number, drugbank_id, dsstox_id, hmdb_id, kegg_id,
            is_corrosive, is_serious_health_hazard, is_explosive, is_flammable, is_gas_under_pressure, is_hazardous2env, is_hazardous2health, is_oxidising, is_toxic,
            is_ed2health, is_ed2env, is_pbt, is_vpvb, is_pmt, is_vpvm, ghs_signal_word,
            canread_base, canwrite_base, canread, canwrite
            ) VALUES (
            :requester, :requester, :name,
            :inchi, :inchi_key,
            :smiles, :molecular_formula, :molecular_weight, :metabolomics_wb_id, :nci_code, :nikkaji_number, :pharmgkb_id, :pharos_ligand_id, :rxcui, :unii, :wikidata, :wikipedia, :is_radioactive, :is_antibiotic, :is_antibiotic_precursor, :is_drug, :is_drug_precursor, :is_explosive_precursor, :is_cmr, :is_nano, :is_controlled,
            :cas_number, :ec_number, :iupac_name, :pubchem_cid, :requester, :team, :chebi_id, :chembl_id, :dea_number, :drugbank_id, :dsstox_id, :hmdb_id, :kegg_id,
            :is_corrosive, :is_serious_health_hazard, :is_explosive, :is_flammable, :is_gas_under_pressure, :is_hazardous2env, :is_hazardous2health, :is_oxidising, :is_toxic, :is_ed2health, :is_ed2env, :is_pbt, :is_pmt, :is_vpvb, :is_vpvm, :ghs_signal_word,
            :canread_base, :canwrite_base, :canread, :canwrite)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester', $this->requester->userid, PDO::PARAM_INT);
        $req->bindParam(':team', $this->requester->team, PDO::PARAM_INT);
        $req->bindParam(':name', $name);
        $req->bindParam(':inchi', $inchi);
        $req->bindParam(':inchi_key', $inchiKey);
        $req->bindParam(':smiles', $smiles);
        $req->bindParam(':molecular_formula', $molecularFormula);
        $req->bindParam(':molecular_weight', $molecularWeight);
        $req->bindParam(':cas_number', $casNumber);
        $req->bindParam(':ec_number', $ecNumber);
        $req->bindParam(':iupac_name', $iupacName);
        $req->bindParam(':pubchem_cid', $pubchemCid);
        $req->bindParam(':chebi_id', $chebiId);
        $req->bindParam(':chembl_id', $chemblId);
        $req->bindParam(':dea_number', $deaNumber);
        $req->bindParam(':drugbank_id', $drugbankId);
        $req->bindParam(':dsstox_id', $dsstoxId);
        $req->bindParam(':hmdb_id', $hmdbId);
        $req->bindParam(':kegg_id', $keggId);
        $req->bindParam(':metabolomics_wb_id', $metabolomicsWbId);
        $req->bindParam(':nci_code', $nciCode);
        $req->bindParam(':nikkaji_number', $nikkajiNumber);
        $req->bindParam(':pharmgkb_id', $pharmGkbId);
        $req->bindParam(':pharos_ligand_id', $pharosLigandId);
        $req->bindParam(':rxcui', $rxcui);
        $req->bindParam(':unii', $unii);
        $req->bindParam(':wikidata', $wikidata);
        $req->bindParam(':wikipedia', $wikipedia);
        $req->bindParam(':is_corrosive', $isCorrosive, PDO::PARAM_INT);
        $req->bindParam(':is_serious_health_hazard', $isSeriousHealthHazard, PDO::PARAM_INT);
        $req->bindParam(':is_explosive', $isExplosive, PDO::PARAM_INT);
        $req->bindParam(':is_flammable', $isFlammable, PDO::PARAM_INT);
        $req->bindParam(':is_gas_under_pressure', $isGasUnderPressure, PDO::PARAM_INT);
        $req->bindParam(':is_hazardous2env', $isHazardous2env, PDO::PARAM_INT);
        $req->bindParam(':is_hazardous2health', $isHazardous2health, PDO::PARAM_INT);
        $req->bindParam(':is_oxidising', $isOxidising, PDO::PARAM_INT);
        $req->bindParam(':is_radioactive', $isRadioactive, PDO::PARAM_INT);
        $req->bindParam(':is_toxic', $isToxic, PDO::PARAM_INT);
        $req->bindParam(':is_antibiotic', $isAntibiotic, PDO::PARAM_INT);
        $req->bindParam(':is_antibiotic_precursor', $isAntibioticPrecursor, PDO::PARAM_INT);
        $req->bindParam(':is_drug', $isDrug, PDO::PARAM_INT);
        $req->bindParam(':is_drug_precursor', $isDrugPrecursor, PDO::PARAM_INT);
        $req->bindParam(':is_explosive_precursor', $isExplosivePrecursor, PDO::PARAM_INT);
        $req->bindParam(':is_cmr', $isCmr, PDO::PARAM_INT);
        $req->bindParam(':is_nano', $isNano, PDO::PARAM_INT);
        $req->bindParam(':is_controlled', $isControlled, PDO::PARAM_INT);
        $req->bindParam(':is_ed2health', $isEd2health, PDO::PARAM_INT);
        $req->bindParam(':is_ed2env', $isEd2env, PDO::PARAM_INT);
        $req->bindParam(':is_pbt', $isPbt, PDO::PARAM_INT);
        $req->bindParam(':is_pmt', $isPmt, PDO::PARAM_INT);
        $req->bindParam(':is_vpvb', $isVpvb, PDO::PARAM_INT);
        $req->bindParam(':is_vpvm', $isVpvm, PDO::PARAM_INT);
        $req->bindValue(':ghs_signal_word', $ghsSignalWord?->value);
        $req->bindValue(':canread_base', $canreadBase->value, PDO::PARAM_INT);
        $req->bindValue(':canwrite_base', $canwriteBase->value, PDO::PARAM_INT);
        $req->bindValue(':canread', $canread);
        $req->bindValue(':canwrite', $canwrite);

        try {
            $this->Db->execute($req);
            // catch the duplicate constraint and upsert existing compound
        } catch (DatabaseErrorException $e) {
            if ($e->getErrorCode() === Db::DUPLICATE_CONSTRAINT_ERROR) {
                // find the compound by comparing unique keys
                $uniqueKeys = array_intersect_key(
                    $compoundData,
                    array_flip(self::UNIQUE_KEYS)
                );
                $existingId = $this->findCompoundByUniqueKey($uniqueKeys);
                if ($existingId === null) {
                    throw new ImproperActionException('Duplicate entry but no existing compound found.');
                }
                return $this->useExistingCompound($existingId, $compoundData, $ghsStatements);
            }
            throw new ImproperActionException(sprintf('Error importing compound: %s', $e->getMessage()));
        }

        $compoundId = $this->Db->lastInsertId();
        new GhsStatements()->linkToCompound($compoundId, $ghsStatements);

        if (!empty($smiles)) {
            $fp = $this->fingerprinter->calculate('smi', $smiles);
            $Fingerprints = new CompoundsFingerprints($compoundId);
            $Fingerprints->create($fp['data']);
        }
        return $compoundId;
    }

    /*
     * Update the existing compound with incoming data.
     * For deleted compounds, restore by setting the state to 1.
     */
    public function upsertCompound(int $id, array $compoundData): int
    {
        $this->setId($id);
        $this->update(new CompoundParams('state', State::Normal->value));
        foreach ($compoundData as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            // Do not update empty pubchem_cid during upsert.
            // This avoids errors on this unique nullable field.
            if ($key === 'pubchem_cid') {
                continue;
            }

            if ($value === true) {
                // because the getContent of compoundParam will use onToBinary
                $value = 'on';
            }
            // without this try catch, sql fails on updating the unique columns with an empty value since it causes many rows with ''.
            try {
                $this->update(new CompoundParams($key, $value));
            } catch (DatabaseErrorException $e) {
                if ($e->getErrorCode() === Db::DUPLICATE_CONSTRAINT_ERROR) {
                    // Skip as the fields ARE nullable so they'll be lots of ''
                    continue;
                }
                throw new ImproperActionException(sprintf('Invalid update on unique column %s', $key));
            }
        }
        return $id;
    }

    /**
     * @param bool $fromPubChem the data was fetched from PubChem by this instance: only then does the compound get the
     * open defaults for PubChem compounds. A PubChem CID alone proves nothing: it can come with local data, like from an import.
     */
    public function createFromCompound(Compound $compound, ?array $permissions = null, bool $fromPubChem = false): int
    {
        $permissions ??= $this->getCreatePermissions(array(), fromPubChem: $fromPubChem);
        return $this->create(
            canreadBase: $permissions['canread_base'],
            canwriteBase: $permissions['canwrite_base'],
            canread: $permissions['canread'],
            canwrite: $permissions['canwrite'],
            casNumber: $compound->cas,
            name: $compound->name,
            inchi: $compound->inChI,
            inchiKey: $compound->inChIKey,
            smiles: $compound->smiles,
            iupacName: $compound->iupacName,
            pubchemCid: $compound->cid,
            molecularFormula: $compound->molecularFormula,
            molecularWeight: $compound->molecularWeight,
            isCorrosive: $compound->isCorrosive,
            isExplosive: $compound->isExplosive,
            isFlammable: $compound->isFlammable,
            isGasUnderPressure: $compound->isGasUnderPressure,
            isHazardous2env: $compound->isHazardous2env,
            isHazardous2health: $compound->isHazardous2health,
            isSeriousHealthHazard: $compound->isSeriousHealthHazard,
            isOxidising: $compound->isOxidising,
            isToxic: $compound->isToxic,
            ghsSignalWord: $compound->ghsSignalWord,
            ghsStatements: $compound->ghsStatements,
        );
    }

    /**
     * The instance setting and the permissions of this compound both let the requester modify it
     */
    public function canWriteCompoundOrExplode(): void
    {
        $this->canWriteOrExplode();
        new CompoundPermissions($this->requester)->canOrExplodeFromId($this->id ?? 0, AccessType::Write);
    }

    /**
     * The instance setting to restrict who can add and edit compounds: the permissions of a compound apply on top of it
     */
    protected function canWrite(): bool
    {
        if ($this->requester instanceof AnonymousUser) {
            return false;
        }
        return $this->requester->userData['can_manage_compounds'] === 1 || $this->requireEditRights === false;
    }

    protected function canWriteOrExplode(): void
    {
        if (!$this->canWrite()) {
            throw new ForbiddenException();
        }
    }

    /**
     * Find existing compound to perform an upsert.
     * Compare in db using unique keys (find at structure.sql - 159)
     */
    private function findCompoundByUniqueKey(array $uniqueKeys): ?int
    {
        // Missing identifiers cannot identify an existing compound.
        $uniqueKeys = array_filter(
            $uniqueKeys,
            static fn($value): bool => $value !== null && (!is_string($value) || trim($value) !== ''),
        );
        if ($uniqueKeys === array()) {
            return null;
        }

        $params = array_map(fn($key) => "$key = :$key", array_keys($uniqueKeys));
        $sql = sprintf(
            'SELECT id FROM compounds WHERE %s LIMIT 1',
            implode(' OR ', $params)
        );
        $req = $this->Db->prepare($sql);

        foreach ($uniqueKeys as $key => $value) {
            $req->bindValue(":$key", $value);
        }
        $this->Db->execute($req);
        $result = $req->fetch(PDO::FETCH_ASSOC);

        return $result ? (int) $result['id'] : null;
    }

    private function canReadOrExplode(): void
    {
        if ($this->requester instanceof AnonymousUser) {
            throw new ForbiddenException();
        }
    }

    private function createCompoundFromIdentifier(array $reqBody, array $permissions): int
    {
        if (!empty($reqBody['cid'])) {
            return $this->createFromCompound($this->searchPubChem((int) $reqBody['cid']), $permissions, fromPubChem: true);
        }
        $cas = $reqBody['cas'] ?? throw new ImproperActionException('Missing pubchem_cid or cas_number');
        return $this->createFromCompound($this->searchPubChemCas($cas)[0], $permissions, fromPubChem: true);
    }

    /**
     * The permissions of a new compound, validated like the ones of an entry, with the defaults for what is not given:
     * the ones of a compound from PubChem, or of a compound added manually
     *
     * @return array{canread_base: BasePermissions, canwrite_base: BasePermissions, canread: string, canwrite: string}
     */
    private function getCreatePermissions(array $reqBody, bool $fromPubChem = false): array
    {
        return array(
            'canread_base' => isset($reqBody['canread_base'])
                ? Check::basePermission((int) $reqBody['canread_base'])
                : ($fromPubChem ? self::PUBCHEM_CANREAD_BASE : self::DEFAULT_CANREAD_BASE),
            'canwrite_base' => isset($reqBody['canwrite_base'])
                ? Check::basePermission((int) $reqBody['canwrite_base'])
                : ($fromPubChem ? self::PUBCHEM_CANWRITE_BASE : self::DEFAULT_CANWRITE_BASE),
            'canread' => isset($reqBody['canread']) ? Check::visibility((string) $reqBody['canread']) : self::EMPTY_CAN_JSON,
            'canwrite' => isset($reqBody['canwrite']) ? Check::visibility((string) $reqBody['canwrite']) : self::EMPTY_CAN_JSON,
        );
    }

    /**
     * Identifiers are unique on the instance, so a new compound can already exist, maybe with other permissions:
     * it is updated with the new data only by someone allowed to write to it, and used as is by someone who can read it.
     * Someone who cannot read it is told it exists: the identifier is all they learn about it.
     */
    private function useExistingCompound(int $id, array $compoundData, array $ghsStatements = array()): int
    {
        $this->setId($id);
        $permissions = new CompoundPermissions($this->requester)->forCompound($this->readRow());
        if ($permissions->write) {
            // an import adds the statements of its source and never removes any: one removed by hand comes back if the source still has it
            new GhsStatements()->linkToCompound($id, $ghsStatements);
            return $this->upsertCompound($id, $compoundData);
        }
        if ($permissions->read) {
            return $id;
        }
        throw new CompoundNotAccessibleException();
    }

    /**
     * GHS statements sent at creation: a list of codes, or a text with codes
     *
     * @return string[]
     */
    private function getGhsCodes(mixed $statements): array
    {
        $text = is_array($statements) ? implode(' ', array_filter($statements, is_string(...))) : (string) $statements;
        return Compound::extractGhsCodes($text);
    }

    /**
     * A compound linked to an entry or used in a reaction cannot be deleted: a reaction checks its linked compounds on every save
     */
    private function assertNotLinkedOrExplode(): void
    {
        $sql = 'SELECT
          CASE
            WHEN EXISTS(SELECT 1 FROM compounds2experiments             WHERE compound_id = :compound_id)
              OR EXISTS(SELECT 1 FROM reaction_components               WHERE compound_id = :compound_id)
              OR EXISTS(SELECT 1 FROM compounds2items                   WHERE compound_id = :compound_id)
              OR EXISTS(SELECT 1 FROM compounds2experiments_templates   WHERE compound_id = :compound_id)
              OR EXISTS(SELECT 1 FROM compounds2items_types             WHERE compound_id = :compound_id)
            THEN 1
            ELSE 0
          END AS is_present';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':compound_id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        if (((int) $req->fetchColumn()) === 1) {
            throw new ImproperActionException(_('Cannot delete a linked compound.'));
        }
    }

    /** The compound with its permission columns, without checking them */
    private function readRow(): array
    {
        $sql = $this->getSelectBeforeWhere() . ' WHERE entity.id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    /**
     * The states, order and limit of readAll(): a fingerprint search lists the most similar compounds first,
     * unless the request asks for another order
     */
    private function getOrderSql(QueryParamsInterface $queryParams, bool $hasSimilarity): string
    {
        if (!$hasSimilarity || $queryParams->getQuery()->has('order')) {
            return $queryParams->getSql();
        }
        $sql = $queryParams->getStatesSql('entity') . ' ORDER BY similarity_score DESC, entity.id DESC';
        if ($queryParams->getLimit() > 0) {
            $sql .= sprintf(' LIMIT %d OFFSET %d', $queryParams->getLimit(), $queryParams->getOffset());
        }
        return $sql;
    }

    /**
     * @param string $similarity the similarity with the searched structure, see FingerprintTable::similaritySql()
     */
    private function getSelectBeforeWhere(string $similarity = ''): string
    {
        return 'SELECT entity.*,' . ($similarity === '' ? '' : sprintf(' %s AS similarity_score,', $similarity)) . '
            CONCAT(
                TO_BASE64(fp0), TO_BASE64(fp1), TO_BASE64(fp2), TO_BASE64(fp3),
                TO_BASE64(fp4), TO_BASE64(fp5), TO_BASE64(fp6), TO_BASE64(fp7),
                TO_BASE64(fp8), TO_BASE64(fp9), TO_BASE64(fp10), TO_BASE64(fp11),
                TO_BASE64(fp12), TO_BASE64(fp13), TO_BASE64(fp14), TO_BASE64(fp15),
                TO_BASE64(fp16), TO_BASE64(fp17), TO_BASE64(fp18), TO_BASE64(fp19),
                TO_BASE64(fp20), TO_BASE64(fp21), TO_BASE64(fp22), TO_BASE64(fp23),
                TO_BASE64(fp24), TO_BASE64(fp25), TO_BASE64(fp26), TO_BASE64(fp27),
                TO_BASE64(fp28), TO_BASE64(fp29), TO_BASE64(fp30), TO_BASE64(fp31)
            ) AS fp2_base64,
            CONCAT(users.firstname, " ", users.lastname) AS userid_human,
            teams.name AS team_name,
            CASE WHEN compounds_fingerprints.id IS NOT NULL THEN 1 ELSE 0 END AS has_fingerprint,
            (SELECT GROUP_CONCAT(gs.code ORDER BY gs.kind, gs.code SEPARATOR ", ")
                FROM compounds2ghs_statements AS c2gs
                INNER JOIN ghs_statements AS gs ON (gs.id = c2gs.statement_id)
                WHERE c2gs.compound_id = entity.id) AS ghs_codes
            FROM compounds AS entity
            LEFT JOIN compounds_fingerprints ON (compounds_fingerprints.id = entity.id)
            LEFT JOIN teams ON (entity.team = teams.id)
            LEFT JOIN users ON (users.userid = entity.userid)';
    }

    /**
     * SMARTS cannot be screened with fingerprints: the structures of the compounds matching the other criteria are read,
     * and Open Babel tells which ones match. Returns the condition on the ids of those that do.
     *
     * @param string $where the other criteria, see readAll()
     */
    private function getSmartsSql(string $pattern, string $where, string $text): string
    {
        if (strlen($pattern) > self::MAX_SMARTS_LENGTH) {
            throw new ImproperActionException(sprintf(_('The SMARTS pattern is limited to %d characters.'), self::MAX_SMARTS_LENGTH));
        }
        $sql = sprintf(
            "SELECT entity.id, entity.smiles FROM compounds AS entity
            LEFT JOIN compounds_fingerprints ON (compounds_fingerprints.id = entity.id)
            WHERE entity.smiles IS NOT NULL AND entity.smiles != ''%s
            LIMIT %d",
            $where,
            self::MAX_SMARTS_CANDIDATES + 1,
        );
        $req = $this->Db->prepare($sql);
        $this->bindSearchValues($req, $text);
        $this->Db->execute($req);
        $rows = $req->fetchAll();
        if (count($rows) > self::MAX_SMARTS_CANDIDATES) {
            throw new ImproperActionException(_('Too many compounds to search with a SMARTS pattern: add other criteria to narrow it.'));
        }
        $matches = $this->smartsMatcher->matches($pattern, array_column($rows, 'smiles'));
        if ($matches === array()) {
            return ' AND 0 = 1';
        }
        // ints from the database: safe to inline
        return sprintf(' AND entity.id IN (%s)', implode(', ', array_map(static fn(int|string $index): int => (int) $rows[$index]['id'], $matches)));
    }

    /**
     * @return list<string>
     */
    private static function getInchiKeys(string $list): array
    {
        if (trim($list) === '') {
            return array();
        }
        $keys = array_values(array_unique(array_map(trim(...), explode(',', $list))));
        if (count($keys) > self::MAX_INCHI_KEYS) {
            throw new ImproperActionException(sprintf('At most %d InChIKeys can be searched at once.', self::MAX_INCHI_KEYS));
        }
        foreach ($keys as $key) {
            if (preg_match(self::INCHI_KEY_REGEX, $key) !== 1) {
                throw new ImproperActionException(sprintf('Invalid InChIKey: %s', $key));
            }
        }
        return $keys;
    }

    // the permissions filter always has :userid, the text search has :query
    private function bindSearchValues(PDOStatement $req, string $text): void
    {
        $req->bindParam(':userid', $this->requester->userid, PDO::PARAM_INT);
        if ($text !== '') {
            $req->bindValue(':query', '%' . $text . '%');
        }
    }

    private function searchPubChem(int $cid): Compound
    {
        $Importer = new PubChemImporter($this->httpGetter, Env::asUrl('PUBCHEM_PUG_URL'), Env::asUrl('PUBCHEM_PUG_VIEW_URL'));
        return $Importer->fromPugView($cid);
    }

    /**
     * @return Compound[]
     */
    private function searchPubChemCas(string $cas): array
    {
        $Importer = new PubChemImporter($this->httpGetter, Env::asUrl('PUBCHEM_PUG_URL'), Env::asUrl('PUBCHEM_PUG_VIEW_URL'));
        $cids = $Importer->getCidFromCas($cas);
        $compounds = array();
        foreach ($cids as $cid) {
            $compounds[] = $this->searchPubChem($cid);
        }
        return $compounds;
    }

    /**
     * @return Compound[]
     */
    private function searchPubChemName(string $name): array
    {
        $Importer = new PubChemImporter($this->httpGetter, Env::asUrl('PUBCHEM_PUG_URL'), Env::asUrl('PUBCHEM_PUG_VIEW_URL'));
        $cids = $Importer->getCidFromName($name);
        $compounds = array();
        foreach ($cids as $cid) {
            $compounds[] = $this->searchPubChem($cid);
        }
        return $compounds;
    }
}

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

use Elabftw\Elabftw\CanSqlBuilder;
use Elabftw\Elabftw\Compound;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Orderby;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\BaseQueryParams;
use Elabftw\Services\Fingerprinter;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\PubChemImporter;
use Elabftw\Traits\SetIdTrait;
use GuzzleHttp\Client;
use Override;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Read chemical compounds from linked with an entity
 */
class Compounds extends AbstractRest
{
    use SetIdTrait;

    protected HttpGetter $httpGetter;

    public function __construct(private Users $requester, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
        $Config = Config::getConfig();
        $this->httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0');
    }

    public function getApiPath(): string
    {
        return 'api/v2/compounds/';
    }

    public function searchPubChem(int $cid): Compound
    {
        $Importer = new PubChemImporter($this->httpGetter);
        return $Importer->fromPugView($cid);
    }

    public function searchFingerprintFromSmiles(string $smiles): array
    {
        $fp = $this->getFingerprintFromSmiles($smiles);
        $sql = 'SELECT id, (BIT_COUNT(';

        // Calculate A ∩ B (bitwise AND) and A + B (bitwise OR) in SQL
        foreach ($fp['data'] as $key => $value) {
            if ($value == 0) {
                continue;
            }
            $sql .= sprintf('(fp%d & %d) | ', $key, $value);
        }
        $sql = rtrim($sql, '| ') . ')) AS similarity_score ';

        $sql .= 'FROM compounds_fingerprints WHERE 1=1';
        foreach ($fp['data'] as $key => $value) {
            if ($value == 0) {
                continue;
            }
            $sql .= sprintf(' AND fp%d & %d = %d', $key, $value, $value);
        }

        $sql .= ' ORDER BY similarity_score, id DESC LIMIT 500';
        $req = $this->Db->prepare($sql);
        $req->execute();
        return $req->fetchAll();
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $queryParams ??= $this->getQueryParams();
        if (!empty($queryParams->getQuery()->get('search_pubchem_cid'))) {
            return $this->searchPubChem($queryParams->getQuery()->getInt('search_pubchem_cid'))->toArray();
        }
        if (!empty($queryParams->getQuery()->get('search_fp_smi'))) {
            return $this->searchFingerprintFromSmiles($queryParams->getQuery()->getString('search_fp_smi'));
        }
        $sql = sprintf('SELECT entity.*,
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
            CASE WHEN compounds_fingerprints.id IS NOT NULL THEN 1 ELSE 0 END AS has_fingerprint
            FROM compounds AS entity
            LEFT JOIN compounds_fingerprints ON (compounds_fingerprints.id = entity.id)
            LEFT JOIN users
            ON (users.userid = entity.userid)
            LEFT JOIN users2teams
                ON (users2teams.users_id = users.userid
                    AND users2teams.teams_id = %d)', $this->requester->team ?? 0);

        $sql .= ' WHERE 1=1';
        // add the json permissions
        $builder = new CanSqlBuilder($this->requester, AccessType::Read);
        $sql .= $builder->getCanFilter();
        if ($queryParams->getQuery()->get('q')) {
            $sql .= ' AND (
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
                entity.wikipedia LIKE :query
            )';
        }
        $sql .= $queryParams->getSql();
        $req = $this->Db->prepare($sql);
        if ($queryParams->getQuery()->get('q')) {
            $req->bindValue(':query', '%' . $queryParams->getQuery()->get('q') . '%');
        }
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
        // check permission
        //$this->canOrExplode(AccessType::Read);
        $sql = 'SELECT * FROM compounds WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->canOrExplode(AccessType::Write);
        $this->update(name: $params['name'] ?? null);
        return $this->readOne();
    }

    public function update(
        ?string $name = null,
    ): bool {
        $sql = 'UPDATE compounds SET name = :name WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':name', $name);

        return $this->Db->execute($req);
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        // TODO add action fromCid or fromSmiles
        // and use fingerprinter
        return match ($action) {
            Action::Duplicate => $this->createFromCid((int) $reqBody['cid']),
            default => $this->create(
                name: $reqBody['name'] ?? null,
                inchi: $reqBody['inchi'] ?? null,
                inchiKey: $reqBody['inchi_key'] ?? null,
                smiles: $reqBody['smiles'] ?? null,
                molecularFormula: $reqBody['molecular_formula'] ?? null,
                casNumber: $reqBody['cas_number'] ?? null,
                iupacName: $reqBody['iupac_name'] ?? null,
                pubchemCid: $reqBody['pubchem_cid'] ?? null,
            ),
        };
    }

    #[Override]
    public function destroy(): bool
    {
        //$this->entity->canOrExplode('write');
        $sql = 'DELETE FROM compounds WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function search(array $fp): array
    {
        $sql = 'SELECT entity_id, entity_type FROM compounds WHERE 1=1';
        foreach ($fp as $key => $value) {
            if ($value == 0) {
                continue;
            }
            $sql .= sprintf(' AND fp%d & %d = %d', $key, $value, $value);
        }
        $req = $this->Db->prepare($sql . ' LIMIT 2');
        $req->execute();
        return $req->fetchAll();
    }

    public function create(
        ?string $canread = null,
        ?string $canwrite = null,
        ?string $inchi = null,
        ?string $inchiKey = null,
        ?string $name = null,
        ?string $smiles = null,
        ?string $molecularFormula = null,
        ?string $casNumber = null,
        ?string $iupacName = null,
        ?int $pubchemCid = null,
        bool $withFingerprint = true,
    ): int {
        $canread ??= BasePermissions::Team->toJson();
        $canwrite ??= BasePermissions::Team->toJson();

        $sql = 'INSERT INTO compounds (
            created_by, modified_by, name, canread, canwrite,
            inchi, inchi_key,
            smiles, molecular_formula,
            cas_number, iupac_name, pubchem_cid, userid, team
            ) VALUES (
            :requester, :requester, :name, :canread, :canwrite,
            :inchi, :inchi_key,
            :smiles, :molecular_formula,
            :cas_number, :iupac_name, :pubchem_cid, :requester, :team)';

        $req = $this->Db->prepare($sql);
        $req->bindParam(':requester', $this->requester->userid);
        $req->bindParam(':team', $this->requester->team);
        $req->bindParam(':name', $name);
        $req->bindParam(':canread', $canread);
        $req->bindParam(':canwrite', $canwrite);
        $req->bindParam(':inchi', $inchi);
        $req->bindParam(':inchi_key', $inchiKey);
        $req->bindParam(':smiles', $smiles);
        $req->bindParam(':molecular_formula', $molecularFormula);
        $req->bindParam(':cas_number', $casNumber);
        $req->bindParam(':iupac_name', $iupacName);
        $req->bindParam(':pubchem_cid', $pubchemCid);

        $this->Db->execute($req);

        $compoundId = $this->Db->lastInsertId();

        if ($withFingerprint && !empty($smiles)) {
            $this->fingerprintCompound($smiles, $compoundId);
        }
        return $compoundId;
    }

    protected function canOrExplode(AccessType $accessType): bool
    {
        if ($this->id === null) {
            throw new ImproperActionException('No id is set!');
        }
        $sql = 'SELECT canread, canwrite, team, userid FROM compounds WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $compound = $req->fetch();
        $Permissions = new Permissions($this->requester, $compound);
        $perms = $Permissions->forEntity();
        return $perms[str_replace('can', '', $accessType->value)] || throw new IllegalActionException(Tools::error(true));
    }

    private function getFingerprintFromSmiles(string $smiles): array
    {
        $Fingerprinter = new Fingerprinter($this->httpGetter);
        return $Fingerprinter->calculate('smi', $smiles);
    }

    private function fingerprintCompound(string $smiles, int $compoundId): int
    {
        $fp = $this->getFingerprintFromSmiles($smiles);
        $Fingerprints = new Fingerprints($compoundId);
        return $Fingerprints->create($fp['data']);
    }

    private function createFromCid(int $cid): int
    {
        $compound = $this->searchPubChem($cid);
        // idea: create from Compound
        return $this->create(
            casNumber: $compound->cas,
            name: $compound->name,
            inchi: $compound->inChI,
            inchiKey: $compound->inChIKey,
            smiles: $compound->smiles,
            iupacName: $compound->iupacName,
            pubchemCid: $cid,
            molecularFormula: $compound->molecularFormula,
        );
    }
}

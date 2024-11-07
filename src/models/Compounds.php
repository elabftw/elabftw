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
use Elabftw\Params\CompoundsQueryParams;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Permissions;
use Elabftw\Elabftw\Tools;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Fingerprinter;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\PubChemImporter;
use Elabftw\Traits\SetIdTrait;
use GuzzleHttp\Client;
use PDO;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * Read chemical compounds from linked with an entity
 */
class Compounds implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    protected HttpGetter $httpGetter;

    public function __construct(private Users $requester, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
        $Config = Config::getConfig();
        $this->httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0');
    }

    public function getApiPath(): string
    {
        return sprintf('api/v2/fingerprints/%d', $this->id ?? 0);
    }

    public function searchPubChem(int $cid): Compound
    {
        $Importer = new PubChemImporter($this->httpGetter);
        return $Importer->fromPugView($cid);
    }

    public function readAll(QueryParamsInterface $queryParams): array
    {
        if (!empty($queryParams->getQuery()->get('search_pubchem_cid'))) {
            return $this->searchPubChem($queryParams->getQuery()->getInt('search_pubchem_cid'))->toArray();
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

        // first WHERE is the state, possibly including archived
        $sql .= sprintf(' WHERE entity.state = %d', State::Normal->value);
        // add the json permissions
        $builder = new CanSqlBuilder($this->requester, AccessType::Read);
        $sql .= $builder->getCanFilter();
        $sql .= $queryParams->getSql();
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();

    }

    public function getQueryParams(InputBag $query): QueryParamsInterface
    {
        return new CompoundsQueryParams($query);
    }

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

        return $this->Db->lastInsertId();
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

    private function createFromCid(int $cid): int
    {
        $compound = $this->searchPubChem($cid);
        // idea: create from Compound
        $id = $this->create(
            casNumber: $compound->cas,
            name: $compound->name,
            inchi: $compound->inChI,
            inchiKey: $compound->inChIKey,
            smiles: $compound->smiles,
            iupacName: $compound->iupacName,
            pubchemCid: $cid,
            molecularFormula: $compound->molecularFormula,
        );
        // Now calculate fingerprint
        $Fingerprinter = new Fingerprinter($this->httpGetter);
        $fp = $Fingerprinter->calculate('smi', $compound->smiles ?? '');
        $Fingerprints = new Fingerprints($id);
        return $Fingerprints->create($fp['data']);
    }
}

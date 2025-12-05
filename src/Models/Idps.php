<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\CertPurpose;
use Elabftw\Enums\IdpsPatchableColumns;
use Elabftw\Enums\SamlBinding;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;

/**
 * An IDP is an Identity Provider. Used in SAML2 authentication context.
 */
final class Idps extends AbstractRest
{
    use SetIdTrait;

    private const string EMAIL_ATTR = 'urn:oid:0.9.2342.19200300.100.1.3';

    private const string TEAM_ATTR = 'urn:oid:1.3.6.1.4.1.5923.1.1.1.7';

    private const string FNAME_ATTR = 'urn:oid:2.5.4.42';

    private const string LNAME_ATTR = 'urn:oid:2.5.4.4';

    private const string ORGID_ATTR = 'urn:oid:0.9.2342.19200300.100.1.1';

    public function __construct(private Users $requester, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return 'api/v2/idps/';
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        $this->requester->isSysadminOrExplode();
        return $this->create(
            name: $reqBody['name'],
            entityid: $reqBody['entityid'],
            email_attr: $reqBody['email_attr'],
            team_attr: $reqBody['team_attr'] ?? null,
            fname_attr: $reqBody['fname_attr'],
            lname_attr: $reqBody['lname_attr'],
            orgid_attr: $reqBody['orgid_attr'] ?? null,
        );
    }

    #[Override]
    public function readOne(): array
    {
        $this->requester->isSysadminOrExplode();
        $sql = sprintf($this->getReadSql(), 'WHERE idps.id = :id');
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->hydrate($this->Db->fetch($req));
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->requester->isSysadminOrExplode();
        // no WHERE clause in readAll
        $sql = sprintf($this->getReadSql(), '');
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        return array_map(array($this, 'hydrate'), $res);
    }

    /**
     * Used to get a list of enabled IDP for the login page, without having to load too much data
     */
    public function readAllSimpleEnabled(): array
    {
        $sql = 'SELECT idps.id, idps.name FROM idps WHERE idps.enabled = 1 ORDER BY name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Used to get a list of IDP for the sysconfig page, without having to load too much data
     */
    public function readAllLight(): array
    {
        $sql = 'SELECT idps.id, idps.name, idps.entityid, idps.enabled, idps_sources.url AS source_url
            FROM idps LEFT JOIN idps_sources ON idps.source = idps_sources.id ORDER BY enabled DESC, name ASC';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        $this->requester->isSysadminOrExplode();
        foreach ($params as $key => $value) {
            if ($key === 'certs' || $key === 'endpoints') {
                continue;
            }
            $this->update(IdpsPatchableColumns::from($key), $value);
        }
        return $this->readOne();
    }

    public function fullUpdate(array $idp): array
    {
        $IdpsCerts = new IdpsCerts($this->requester, $this->id);
        $IdpsCerts->sync($this->id ?? 0, $idp);
        $IdpsEndpoints = new IdpsEndpoints($this->requester, $this->id);
        $IdpsEndpoints->sync($this->id ?? 0, $idp);
        unset($idp['certs']);
        unset($idp['endpoints']);
        foreach ($idp as $key => $value) {
            $this->update(IdpsPatchableColumns::from($key), $value);
        }
        return $this->readOne();
    }

    public function upsert(int $sourceId, array $idps): int
    {
        foreach ($idps as $idp) {
            $id = $this->findByEntityId($idp['entityid']);
            if ($id === 0) {
                $this->create(
                    name: $idp['name'],
                    entityid: $idp['entityid'],
                    enabled: 0,
                    source: $sourceId,
                    certs: $idp['certs'],
                    endpoints: $idp['endpoints'],
                );
                continue;
            }
            $this->setId($id);
            // when coming from XML, we do not overwrite these attributes
            $immutableFields = array('email_attr', 'fname_attr', 'lname_attr', 'team_attr', 'orgid_attr');
            foreach ($immutableFields as $key) {
                unset($idp[$key]);
            }
            $this->fullUpdate($idp);
        }
        return count($idps);
    }

    public function getEnabled(?int $id = null): int
    {
        $sql = 'SELECT id FROM idps WHERE enabled = 1';
        if ($id !== null) {
            $sql .= ' AND id = :id';
        }
        $req = $this->Db->prepare($sql);
        if ($id !== null) {
            $req->bindParam(':id', $id, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    public function getEnabledByEntityId(string $entId): int
    {
        $sql = 'SELECT id FROM idps WHERE enabled = 1 AND entityid = :entId';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entId', $entId);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    #[Override]
    public function destroy(): bool
    {
        $this->requester->isSysadminOrExplode();
        $sql = 'DELETE FROM idps WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    public function create(
        string $name,
        string $entityid,
        string $email_attr = self::EMAIL_ATTR,
        ?string $team_attr = self::TEAM_ATTR,
        string $fname_attr = self::FNAME_ATTR,
        string $lname_attr = self::LNAME_ATTR,
        ?string $orgid_attr = self::ORGID_ATTR,
        int $enabled = 1,
        ?int $source = null,
        ?array $certs = array(),
        ?array $endpoints = array(),
    ): int {
        $idpId = $this->createIdp($name, $entityid, $email_attr, $team_attr, $fname_attr, $lname_attr, $orgid_attr, $enabled, $source);
        if (!empty($certs)) {
            $IdpsCerts = new IdpsCerts($this->requester, $idpId);
            foreach ($certs as $cert) {
                $IdpsCerts->create($cert['purpose'], $cert['x509'], $cert['sha256'], $cert['not_before'], $cert['not_after']);
            }
        }
        if (!empty($endpoints)) {
            $IdpsEndpoints = new IdpsEndpoints($this->requester, $idpId);
            foreach ($endpoints as $endpoint) {
                $IdpsEndpoints->create($endpoint['binding'], $endpoint['location'], $endpoint['is_slo']);
            }
        }

        return $idpId;
    }

    public function findByEntityId(string $entityId): int
    {
        $sql = 'SELECT id FROM idps WHERE entityid = :entityId';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entityId', $entityId);
        $this->Db->execute($req);
        $res = $req->fetchColumn();
        if ($res === false) {
            return 0;
        }
        return (int) $res;
    }

    /**
     * Returns a formatable string with a placeholder for the WHERE
     */
    private function getReadSql(): string
    {
        return "SELECT idps.*, idps_sources.url AS source_url,
            COALESCE(
                (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    'id', ic.id,
                    'x509', ic.x509,
                    'sha256', ic.sha256,
                    'purpose', ic.purpose,
                    'not_before', ic.not_before,
                    'not_after', ic.not_after,
                    'created_at', ic.created_at,
                    'modified_at', ic.modified_at))
                FROM idps_certs AS ic
                WHERE ic.idp = idps.id),
                JSON_ARRAY()
            ) AS certs,
            COALESCE(
                (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    'id', ie.id,
                    'binding', ie.binding,
                    'location', ie.location,
                    'is_slo', ie.is_slo,
                    'created_at', ie.created_at,
                    'modified_at', ie.modified_at))
                FROM idps_endpoints AS ie
                WHERE ie.idp = idps.id),
                JSON_ARRAY()
            ) AS endpoints
            FROM idps
            LEFT JOIN idps_sources ON idps.source = idps_sources.id
            %s
            ORDER BY name ASC";
    }

    private function hydrate(array $idp): array
    {
        $idp['certs'] = array_map(
            static fn(array $cert): array => $cert + array(
                'purpose_human' => CertPurpose::from($cert['purpose'])->name,
            ),
            json_decode($idp['certs'] ?? 'null', true, 3, JSON_THROW_ON_ERROR) ?? array()
        );

        $idp['endpoints'] = array_map(
            static fn(array $endpoint): array => $endpoint + array(
                'binding_urn' => SamlBinding::from($endpoint['binding'])->toUrn(),
            ),
            json_decode($idp['endpoints'] ?? 'null', true, 3, JSON_THROW_ON_ERROR) ?? array()
        );
        return $idp;
    }

    private function createIdp(
        string $name,
        string $entityid,
        string $email_attr = self::EMAIL_ATTR,
        ?string $team_attr = self::TEAM_ATTR,
        string $fname_attr = self::FNAME_ATTR,
        string $lname_attr = self::LNAME_ATTR,
        ?string $orgid_attr = self::ORGID_ATTR,
        int $enabled = 1,
        ?int $source = null,
    ): int {
        $sql = 'INSERT INTO idps(name, entityid, email_attr, team_attr, fname_attr, lname_attr, orgid_attr, enabled, source)
            VALUES(:name, :entityid, :email_attr, :team_attr, :fname_attr, :lname_attr, :orgid_attr, :enabled, :source)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':email_attr', $email_attr);
        $req->bindParam(':team_attr', $team_attr);
        $req->bindParam(':fname_attr', $fname_attr);
        $req->bindParam(':lname_attr', $lname_attr);
        $req->bindParam(':orgid_attr', $orgid_attr);
        $req->bindParam(':enabled', $enabled, PDO::PARAM_INT);
        $req->bindParam(':source', $source);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    private function update(IdpsPatchableColumns $target, string $value): array
    {
        $sql = sprintf('UPDATE idps SET %s = :value WHERE id = :id', $target->value);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':value', $value);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->readOne();
    }
}

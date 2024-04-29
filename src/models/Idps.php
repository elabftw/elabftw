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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * An IDP is an Identity Provider. Used in SAML2 authentication context.
 */
class Idps implements RestInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function getPage(): string
    {
        return 'api/v2/idps/';
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $sql = 'INSERT INTO idps(name, entityid, sso_url, sso_binding, slo_url, slo_binding, x509, x509_new, email_attr, team_attr, fname_attr, lname_attr, orgid_attr)
            VALUES(:name, :entityid, :sso_url, :sso_binding, :slo_url, :slo_binding, :x509, :x509_new, :email_attr, :team_attr, :fname_attr, :lname_attr, :orgid_attr)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $reqBody['name']);
        $req->bindParam(':entityid', $reqBody['entityid']);
        $req->bindParam(':sso_url', $reqBody['sso_url']);
        $req->bindParam(':sso_binding', $reqBody['sso_binding']);
        $req->bindParam(':slo_url', $reqBody['slo_url']);
        $req->bindParam(':slo_binding', $reqBody['slo_binding']);
        $req->bindParam(':x509', $reqBody['x509']);
        $req->bindParam(':x509_new', $reqBody['x509_new']);
        $req->bindParam(':email_attr', $reqBody['email_attr']);
        $req->bindParam(':team_attr', $reqBody['team_attr']);
        $req->bindParam(':fname_attr', $reqBody['fname_attr']);
        $req->bindParam(':lname_attr', $reqBody['lname_attr']);
        $req->bindParam(':orgid_attr', $reqBody['orgid_attr']);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function readOne(): array
    {
        $sql = 'SELECT * FROM idps WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function readAll(): array
    {
        $sql = 'SELECT * FROM idps ORDER BY name';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        foreach ($params as $key => $value) {
            $this->update($key, $value);
        }
        return $this->readOne();
    }

    /**
     * Get an enabled IDP
     */
    public function getEnabled(?int $id = null): array
    {
        $sql = 'SELECT * FROM idps WHERE enabled = 1';
        if ($id !== null) {
            $sql .= ' AND id = :id';
        }
        $req = $this->Db->prepare($sql);
        if ($id !== null) {
            $req->bindParam(':id', $id, PDO::PARAM_INT);
        }
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    /**
     * Get enabled IDP by entity id
     */
    public function getEnabledByEntityId(string $entId): array
    {
        $sql = 'SELECT * FROM idps WHERE enabled = 1 AND entityid = :entId';
        $req = $this->Db->prepare($sql);

        $req->bindParam(':entId', $entId);
        $this->Db->execute($req);

        return $this->Db->fetch($req);
    }

    public function destroy(): bool
    {
        $sql = 'DELETE FROM idps WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    private function update(string $target, string $value): array
    {
        $sql = 'UPDATE idps SET ' . $target . ' = :value WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':value', $value);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->readOne();
    }
}

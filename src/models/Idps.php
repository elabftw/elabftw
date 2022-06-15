<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\DestroyableInterface;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * Store information about different identity providers for auth with SAML
 */
class Idps implements DestroyableInterface
{
    use SetIdTrait;

    protected Db $Db;

    public function __construct(?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    /**
     * Create an IDP
     */
    public function create(
        string $name,
        string $entityid,
        string $ssoUrl,
        string $ssoBinding,
        string $sloUrl,
        string $sloBinding,
        string $x509,
        string $x509_new,
        string $active,
        string $emailAttr,
        string $teamAttr,
        string $fnameAttr,
        string $lnameAttr,
    ): int {
        $sql = 'INSERT INTO idps(name, entityid, sso_url, sso_binding, slo_url, slo_binding, x509, x509_new, active, email_attr, team_attr, fname_attr, lname_attr)
            VALUES(:name, :entityid, :sso_url, :sso_binding, :slo_url, :slo_binding, :x509, :x509_new, :active, :email_attr, :team_attr, :fname_attr, :lname_attr)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':sso_url', $ssoUrl);
        $req->bindParam(':sso_binding', $ssoBinding);
        $req->bindParam(':slo_url', $sloUrl);
        $req->bindParam(':slo_binding', $sloBinding);
        $req->bindParam(':x509', $x509);
        $req->bindParam(':x509_new', $x509_new);
        $req->bindParam(':active', $active);
        $req->bindParam(':email_attr', $emailAttr);
        $req->bindParam(':team_attr', $teamAttr);
        $req->bindParam(':fname_attr', $fnameAttr);
        $req->bindParam(':lname_attr', $lnameAttr);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function readAll(): array
    {
        $sql = 'SELECT * FROM idps';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Update info about an IDP
     */
    public function update(
        int $id,
        string $name,
        string $entityid,
        string $ssoUrl,
        string $ssoBinding,
        string $sloUrl,
        string $sloBinding,
        string $x509,
        string $x509_new,
        string $active,
        string $emailAttr,
        string $teamAttr,
        string $fnameAttr,
        string $lnameAttr
    ): bool {
        $sql = 'UPDATE idps SET
            name = :name,
            entityid = :entityid,
            sso_url = :sso_url,
            sso_binding = :sso_binding,
            slo_url = :slo_url,
            slo_binding = :slo_binding,
            x509 = :x509,
            x509_new = :x509_new,
            active = :active,
            email_attr = :email_attr,
            team_attr = :team_attr,
            fname_attr = :fname_attr,
            lname_attr = :lname_attr
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':sso_url', $ssoUrl);
        $req->bindParam(':sso_binding', $ssoBinding);
        $req->bindParam(':slo_url', $sloUrl);
        $req->bindParam(':slo_binding', $sloBinding);
        $req->bindParam(':x509', $x509);
        $req->bindParam(':x509_new', $x509_new);
        $req->bindParam(':active', $active);
        $req->bindParam(':email_attr', $emailAttr);
        $req->bindParam(':team_attr', $teamAttr);
        $req->bindParam(':fname_attr', $fnameAttr);
        $req->bindParam(':lname_attr', $lnameAttr);
        return $this->Db->execute($req);
    }

    /**
     * Get an active IDP
     */
    public function getActive(?int $id = null): array
    {
        $sql = 'SELECT * FROM idps WHERE active = 1';
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
     * Get active IDP by entity id
     */
    public function getActiveByEntityId(string $entId): array
    {
        $sql = 'SELECT * FROM idps WHERE active = 1 AND entityid = :entId';
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
}

<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use PDO;

/**
 * Store informations about different identity providers for auth with SAML
 */
class Idps implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Create an IDP
     *
     * @param string $name
     * @param string $entityid
     * @param string $ssoUrl Single Sign On URL
     * @param string $ssoBinding
     * @param string $sloUrl Single Log Out URL
     * @param string $sloBinding
     * @param string $x509 Public x509 Certificate
     * @param string $active 0 or 1
     *
     * @return int last insert id
     */
    public function create(string $name, string $entityid, string $ssoUrl, string $ssoBinding, string $sloUrl, string $sloBinding, string $x509, string $active): int
    {
        $sql = "INSERT INTO idps(name, entityid, sso_url, sso_binding, slo_url, slo_binding, x509, active)
            VALUES(:name, :entityid, :sso_url, :sso_binding, :slo_url, :slo_binding, :x509, :active)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':sso_url', $ssoUrl);
        $req->bindParam(':sso_binding', $ssoBinding);
        $req->bindParam(':slo_url', $sloUrl);
        $req->bindParam(':slo_binding', $sloBinding);
        $req->bindParam(':x509', $x509);
        $req->bindParam(':active', $active);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        return $this->Db->lastInsertId();
    }

    /**
     * Read info about an IDP
     *
     * @param int $id
     * @return array
     */
    public function read(int $id): array
    {
        $sql = "SELECT * FROM idps WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
        $res = $req->fetch();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Read all IDPs
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = "SELECT * FROM idps";
        $req = $this->Db->prepare($sql);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Update info about an IDP
     *
     * @param int $id
     * @param string $name
     * @param string $entityid
     * @param string $ssoUrl Single Sign On URL
     * @param string $ssoBinding
     * @param string $sloUrl Single Log Out URL
     * @param string $sloBinding
     * @param string $x509 Public x509 Certificate
     * @param string $active 0 or 1
     * @return void
     */
    public function update(int $id, string $name, string $entityid, string $ssoUrl, string $ssoBinding, string $sloUrl, string $sloBinding, string $x509, string $active): void
    {
        $sql = "UPDATE idps SET
            name = :name,
            entityid = :entityid,
            sso_url = :sso_url,
            sso_binding = :sso_binding,
            slo_url = :slo_url,
            slo_binding = :slo_binding,
            x509 = :x509,
            active = :active
            WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':sso_url', $ssoUrl);
        $req->bindParam(':sso_binding', $ssoBinding);
        $req->bindParam(':slo_url', $sloUrl);
        $req->bindParam(':slo_binding', $sloBinding);
        $req->bindParam(':x509', $x509);
        $req->bindParam(':active', $active);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Get an active IDP
     *
     * @return array
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM idps WHERE active = 1 LIMIT 1";
        $req = $this->Db->prepare($sql);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('Could not find active IDP!');
        }
        return $res;
    }

    /**
     * Destroy an IDP
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $sql = "DELETE FROM idps WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Not implemented
     *
     * @return void
     */
    public function destroyAll(): void
    {
        return;
    }
}

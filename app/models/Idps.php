<?php
/**
 * \Elabftw\Elabftw\Idps
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

/**
 * Store informations about different identity providers for auth with SAML
 */
class Idps
{
    /** db connection */
    protected $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
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
     * @return bool
     */
    public function create($name, $entityid, $ssoUrl, $ssoBinding, $sloUrl, $sloBinding, $x509)
    {
        $sql = "INSERT INTO idps(name, entityid, sso_url, sso_binding, slo_url, slo_binding, x509)
            VALUES(:name, :entityid, :sso_url, :sso_binding, :slo_url, :slo_binding, :x509)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':sso_url', $ssoUrl);
        $req->bindParam(':sso_binding', $ssoBinding);
        $req->bindParam(':slo_url', $sloUrl);
        $req->bindParam(':slo_binding', $sloBinding);
        $req->bindParam(':x509', $x509);

        return $req->execute();
    }

    /**
     * Read info about an IDP
     *
     * @param int $id
     * @return array
     */
    public function read($id)
    {
        $sql = "SELECT * FROM idps WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read all IDPs
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT * FROM idps";
        $req = $this->pdo->prepare($sql);
        $req->execute();

        return $req->fetchAll();
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
     * @return bool
     */
    public function update($id, $name, $entityid, $ssoUrl, $ssoBinding, $sloUrl, $sloBinding, $x509)
    {
        $sql = "UPDATE idps SET
            name = :name,
            entityid = :entityid,
            sso_url = :sso_url,
            sso_binding = :sso_binding,
            slo_url = :slo_url,
            slo_binding = :slo_binding,
            x509 = :x509
            WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':name', $name);
        $req->bindParam(':entityid', $entityid);
        $req->bindParam(':sso_url', $ssoUrl);
        $req->bindParam(':sso_binding', $ssoBinding);
        $req->bindParam(':slo_url', $sloUrl);
        $req->bindParam(':slo_binding', $sloBinding);
        $req->bindParam(':x509', $x509);

        return $req->execute();
    }

    /**
     * Destroy an IDP
     *
     * @param int $id
     * @return bool
     */
    public function destroy($id)
    {
        $sql = "DELETE FROM idps WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);

        return $req->execute();
    }
}

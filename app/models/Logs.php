<?php
/**
 * \Elabftw\Elabftw\Logs
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * All about the teams
 */
class Logs implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Constructor
     *
     * @throws Exception if user is not admin
     */
    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Insert a log entry in the logs table
     *
     * @param string $type The type of the log. Can be 'Error', 'Warning', 'Info'
     * @param string $user id of an user or ip address if not auth
     * @param string $body The content of the log
     * @return bool Will return true if the query is successfull
     */
    public function create($type, $user, $body)
    {
        $sql = "INSERT INTO logs (type, user, body) VALUES (:type, :user, :body)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':type', $type);
        $req->bindParam(':user', $user);
        $req->bindParam(':body', $body);

        return $req->execute();
    }

    /**
     * Read the logs
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT * FROM logs ORDER BY id DESC LIMIT 100";
        $req = $this->Db->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Not implemented
     *
     * @param $id
     */
    public function destroy($id)
    {
    }

    /**
     * Clear the logs
     *
     * @return bool
     */
    public function destroyAll()
    {
        $sql = "DELETE FROM logs";
        $req = $this->Db->prepare($sql);

        return $req->execute();
    }
}

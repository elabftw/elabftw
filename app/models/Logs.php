<?php
/**
 * \Elabftw\Elabftw\Logs
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * All about the teams
 */
class Logs extends Panel
{
    /** pdo object */
    protected $pdo;

    /**
     * Constructor
     *
     * @throws Exception if user is not admin
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
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
        $req = $this->pdo->prepare($sql);
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
    public function read()
    {
        if (!$this->isSysAdmin()) {
            throw new Exception('Only admin can access this!');
        }

        $sql = "SELECT * FROM logs ORDER BY id DESC LIMIT 100";
        $req = $this->pdo->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Clear the logs
     *
     * @return bool
     */
    public function destroy()
    {
        if (!$this->isSysAdmin()) {
            throw new Exception('Only admin can access this!');
        }

        $sql = "DELETE FROM logs";
        $req = $this->pdo->prepare($sql);

        return $req->execute();
    }
}

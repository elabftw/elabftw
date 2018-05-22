<?php
/**
 * \Elabftw\Elabftw\Logs
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;

/**
 * All about the logs
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
    public function create(string $type, string $user, string $body): bool
    {
        $sql = 'INSERT INTO logs (type, user, body) VALUES (:type, :user, :body)';
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
    public function readAll(): array
    {
        $sql = 'SELECT * FROM logs ORDER BY id DESC LIMIT 100';
        $req = $this->Db->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Not implemented
     *
     * @param int $id
     * @return bool
     */
    public function destroy(int $id): bool
    {
        return false;
    }

    /**
     * Clear the logs
     *
     * @return bool
     */
    public function destroyAll(): bool
    {
        $sql = 'DELETE FROM logs';
        $req = $this->Db->prepare($sql);

        return $req->execute();
    }
}

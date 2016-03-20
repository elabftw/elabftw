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

use \PDO;
use \Exception;

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
        if (!$this->isSysAdmin()) {
            throw new Exception('Only admin can access this!');
        }
    }

    /**
     * Read the logs
     *
     * @return array
     */
    public function read()
    {
        $sql = "SELECT * FROM logs ORDER BY id DESC LIMIT 100";
        $req = $this->pdo->prepare($sql);
        $req->execute();

        return $req->fetchAll();
    }
}

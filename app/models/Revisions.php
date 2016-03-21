<?php
/**
 * \Elabftw\Elabftw\Revisions
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
 * All about the revisions
 */
class Revisions
{
    /** pdo object */
    private $pdo;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Get how many revisions we have
     *
     * @param int $experiment ID of the experiment
     */
    public function readCount($experiment)
    {
        $sql = "SELECT COUNT(*) FROM experiments_revisions
            WHERE item_id = :item_id ORDER BY savedate DESC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':item_id', $experiment);
        $req->execute();

        return (int) $req->fetchColumn();
    }
}

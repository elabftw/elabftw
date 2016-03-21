<?php
/**
 * \Elabftw\Elabftw\Uploads
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
 * All about the file uploads
 */
class Uploads
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

    public function read($id, $type)
    {
        // Check that the item we view has attached files
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':type', $type);
        $req->execute();

        return $req->fetchAll();
    }
}

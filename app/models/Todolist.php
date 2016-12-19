<?php
/**
 * \Elabftw\Elabftw\Todolist
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use PDO;
use Exception;

/**
 * All about the todolist
 */
class Todolist
{
    /** pdo object */
    protected $pdo;

    /** our user */
    private $userid;

    public function __construct($userid)
    {
        $this->pdo = Db::getConnection();
        $this->userid = $userid;
    }

    public function create($body)
    {
        $sql = "INSERT INTO todolist(body, userid)
            VALUES(:body, :userid)";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->userid);

        if (!$req->execute()) {
            throw new Exception('Error inserting todoitem!');
        }

        return $this->pdo->lastInsertId();
    }

    public function readAll()
    {
        $sql = "SELECT id, body, creation_time FROM todolist WHERE userid = :userid ORDER BY ordering ASC";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->userid);
        $req->execute();

        return $req->fetchAll();
    }

    public function updateOrdering($post)
    {
        $success = array();

        foreach ($post['ordering'] as $ordering => $id) {
            $id = explode('_', $id);
            $id = $id[1];
            // update the ordering
            $sql = "UPDATE todolist SET ordering = :ordering WHERE id = :id AND userid = :userid";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $req->bindParam(':userid', $this->userid);
            $success[] = $req->execute();
        }

        return !in_array(false, $success);
    }

    public function destroy($id)
    {
        $sql = "DELETE FROM todolist WHERE id = :id AND userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }

    public function destroyAll()
    {
        $sql = "DELETE FROM todolist WHERE userid = :userid";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }
}

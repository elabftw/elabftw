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
class Todolist implements CrudInterface
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /** our user */
    private $userid;

    /**
     * Gimme a userid
     *
     * @param int $userid
     */
    public function __construct($userid)
    {
        $this->Db = Db::getConnection();
        $this->userid = $userid;
    }

    /**
     * Create a todoitem
     *
     * @param string $body
     * @return string the id of the created todoitem
     */
    public function create($body)
    {
        $sql = "INSERT INTO todolist(body, userid)
            VALUES(:body, :userid)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->userid);

        if (!$req->execute()) {
            throw new Exception('Error inserting todoitem!');
        }

        return $this->Db->lastInsertId();
    }

    /**
     * Select all the todoitems for a user
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT id, body, creation_time FROM todolist WHERE userid = :userid ORDER BY ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid);
        $req->execute();

        return $req->fetchAll();
    }

    /**
     * Update the body of a todoitem with jeditable
     *
     * @param int $id Id of the todoitem
     * @param string $body
     * @return bool
     */
    public function update($id, $body)
    {
        $sql = "UPDATE todolist SET body = :body WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':body', $body);

        return $req->execute();
    }

    /**
     * Update the order of the todoitems
     *
     * @param array $post
     * @return bool
     */
    public function updateOrdering($post)
    {
        $success = array();

        foreach ($post['ordering'] as $ordering => $id) {
            $id = explode('_', $id);
            $id = $id[1];
            // update the ordering
            $sql = "UPDATE todolist SET ordering = :ordering WHERE id = :id AND userid = :userid";
            $req = $this->Db->prepare($sql);
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $req->bindParam(':userid', $this->userid);
            $success[] = $req->execute();
        }

        return !in_array(false, $success);
    }

    /**
     * Remove a todoitem
     *
     * @param int $id
     * @return bool
     */
    public function destroy($id)
    {
        $sql = "DELETE FROM todolist WHERE id = :id AND userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }

    /**
     * Clear all todoitems from the todolist
     *
     * @return bool
     */
    public function destroyAll()
    {
        $sql = "DELETE FROM todolist WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->userid);

        return $req->execute();
    }
}

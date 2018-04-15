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
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use PDO;

/**
 * All about the todolist
 */
class Todolist implements CrudInterface
{
    use EntityTrait;

    /**
     * Gimme a userid
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
    }

    /**
     * Create a todoitem
     *
     * @param string $body
     * @return int the id of the created todoitem
     */
    public function create(string $body): int
    {
        $sql = "INSERT INTO todolist(body, userid)
            VALUES(:body, :userid)";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userid);

        if (!$req->execute()) {
            throw new Exception('Error inserting todoitem!');
        }

        return (int) $this->Db->lastInsertId();
    }

    /**
     * Select all the todoitems for a user
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = "SELECT id, body, creation_time FROM todolist WHERE userid = :userid ORDER BY ordering ASC";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid);
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
    public function update(int $id, string $body): bool
    {
        $sql = "UPDATE todolist SET body = :body WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id);
        $req->bindParam(':body', $body);

        return $req->execute();
    }

    /**
     * Remove a todoitem
     *
     * @param int $id
     * @return bool
     */
    public function destroy(int $id): bool
    {
        $sql = "DELETE FROM todolist WHERE id = :id AND userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userid);

        return $req->execute();
    }

    /**
     * Clear all todoitems from the todolist
     *
     * @return bool
     */
    public function destroyAll(): bool
    {
        $sql = "DELETE FROM todolist WHERE userid = :userid";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userid);

        return $req->execute();
    }
}

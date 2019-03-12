<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Interfaces\CreateInterface;
use PDO;

/**
 * All about the database items
 */
class Database extends AbstractEntity implements CreateInterface
{
    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id id of the item
     */
    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->type = 'items';
        $this->page = 'database';
    }

    /**
     * Create an item
     *
     * @param int $category What kind of item we want to create.
     * @return int the new id of the item
     */
    public function create(int $category): int
    {
        $itemsTypes = new ItemsTypes($this->Users, $category);

        // SQL for create DB item
        $sql = "INSERT INTO items(team, title, date, body, userid, category)
            VALUES(:team, :title, :date, :body, :userid, :category)";
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => _('Untitled'),
            'date' => Tools::kdate(),
            'body' => $itemsTypes->read(),
            'userid' => $this->Users->userData['userid'],
            'category' => $category
        ));

        return $this->Db->lastInsertId();
    }

    /**
     * Update the rating of an item
     *
     * @param int $rating
     * @return void
     */
    public function updateRating(int $rating): void
    {
        $this->canOrExplode('write');

        $sql = 'UPDATE items SET rating = :rating WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':rating', $rating, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }

    /**
     * Duplicate an item
     *
     * @return int The id of the newly created item
     */
    public function duplicate(): int
    {
        $sql = "INSERT INTO items(team, title, date, body, userid, category)
            VALUES(:team, :title, :date, :body, :userid, :category)";
        $req = $this->Db->prepare($sql);
        $req->execute(array(
            'team' => $this->Users->userData['team'],
            'title' => $this->entityData['title'],
            'date' => Tools::kdate(),
            'body' => $this->entityData['body'],
            'userid' => $this->Users->userData['userid'],
            'category' => $this->entityData['category_id']
        ));
        $newId = $this->Db->lastInsertId();

        $this->Tags->copyTags($newId);

        return $newId;
    }

    /**
     * Destroy a DB item
     *
     * @return void
     */
    public function destroy(): void
    {
        // delete the database item
        $sql = "DELETE FROM items WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        $this->Tags->destroyAll();

        $this->Uploads->destroyAll();

        // delete links of this item in experiments with this item linked
        // get all experiments with that item linked
        $sql = "SELECT id FROM experiments_links WHERE link_id = :link_id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $this->id, PDO::PARAM_INT);
        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }

        while ($links = $req->fetch()) {
            $delete_sql = "DELETE FROM experiments_links WHERE id = :links_id";
            $delete_req = $this->Db->prepare($delete_sql);
            $delete_req->bindParam(':links_id', $links['id'], PDO::PARAM_INT);
            if ($delete_req->execute() !== true) {
                throw new DatabaseErrorException('Error while executing SQL query.');
            }
        }
    }

    /**
     * Lock or unlock an item
     *
     * @return void
     */
    public function toggleLock(): void
    {
        $sql = "UPDATE items SET locked = IF(locked = 1, 0, 1) WHERE id = :id";
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($req->execute() !== true) {
            throw new DatabaseErrorException('Error while executing SQL query.');
        }
    }
}

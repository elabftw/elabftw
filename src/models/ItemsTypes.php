<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\ItemTypeParamsInterface;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends AbstractEntity
{
    use SortableTrait;

    private int $team;

    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->team = $this->Users->team;
        $this->Links = new Links($this);
        $this->Steps = new Steps($this);
        $this->type = 'items_types';
        if ($id !== null) {
            $this->setId($id);
        }
    }

    public function create(ItemTypeParamsInterface $params): int
    {
        $sql = 'INSERT INTO items_types(name, team) VALUES(:content, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getTitle(), PDO::PARAM_STR);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'all') {
            return $this->readAll();
        }

        $sql = 'SELECT id, team, color, bookable, name, body, canread, canwrite, metadata
            FROM items_types WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    public function duplicate(): int
    {
        return 1;
    }

    public function updateAll(ItemTypeParamsInterface $params): bool
    {
        $sql = 'UPDATE items_types SET
            name = :name,
            team = :team,
            color = :color,
            bookable = :bookable,
            body = :body,
            canread = :canread,
            canwrite = :canwrite
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent(), PDO::PARAM_STR);
        $req->bindValue(':color', $params->getColor(), PDO::PARAM_STR);
        $req->bindValue(':bookable', $params->getIsBookable(), PDO::PARAM_INT);
        $req->bindValue(':body', $params->getBody(), PDO::PARAM_STR);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindValue(':canread', $params->getCanread(), PDO::PARAM_STR);
        $req->bindValue(':canwrite', $params->getCanwriteS(), PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Destroy an item type
     *
     */
    public function destroy(): bool
    {
        // don't allow deletion of an item type with items
        if ($this->countItems() > 0) {
            throw new ImproperActionException(_('Remove all database items with this type before deleting this type.'));
        }
        $sql = 'DELETE FROM items_types WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * SQL to get all items type
     */
    public function readAll(bool $getTags = true): array
    {
        $sql = 'SELECT items_types.id AS category_id,
            items_types.name AS category,
            items_types.color,
            items_types.bookable,
            items_types.body,
            items_types.ordering,
            items_types.canread,
            items_types.canwrite
            FROM items_types WHERE team = :team ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->fetchAll($req);
    }

    /**
     * Count all items of this type
     * TODO have a countable interface and maybe counttrait to merge this function with Status
     */
    protected function countItems(): int
    {
        $sql = 'SELECT COUNT(id) FROM items WHERE category = :category';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }
}

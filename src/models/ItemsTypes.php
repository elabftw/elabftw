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
use Elabftw\Traits\CategoryTrait;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends AbstractTemplateEntity
{
    use SortableTrait;
    use CategoryTrait;

    private int $team;

    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->type = parent::TYPE_ITEMS_TYPES;
        $this->Db = Db::getConnection();
        $this->team = $this->Users->team;
        $this->Links = new Links($this);
        $this->countableTable = 'items';
        $this->Steps = new Steps($this);
        if ($id !== null) {
            $this->setId($id);
        }
    }

    public function getPage(): string
    {
        return 'admin.php?tab=5&templateid=';
    }

    public function create(string $title): int
    {
        $sql = 'INSERT INTO items_types(title, team) VALUES(:content, :team)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $title, PDO::PARAM_STR);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * SQL to get all items type
     */
    public function readAll(): array
    {
        $sql = 'SELECT items_types.id AS category_id,
            items_types.title AS category,
            items_types.color,
            items_types.bookable,
            items_types.body,
            items_types.ordering,
            items_types.canread,
            items_types.canwrite
            FROM items_types WHERE team = :team AND state = :state ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindValue(':state', self::STATE_NORMAL, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT id, team, color, bookable, title, body, canread, canwrite, metadata, state
            FROM items_types WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->entityData = $this->Db->fetch($req);
        // don't check for read permissions for items types as it can be read from many places/users
        //$this->canOrExplode('read');
        // add steps and links in there too
        $this->entityData['steps'] = $this->Steps->readAll();
        $this->entityData['links'] = $this->Links->readAll();
        return $this->entityData;
    }

    public function duplicate(): int
    {
        return 1;
    }

    /**
     * Destroy an item type
     */
    public function destroy(): bool
    {
        // don't allow deletion of an item type with items
        if ($this->countEntities() > 0) {
            throw new ImproperActionException(_('Remove all database items with this type before deleting this type.'));
        }

        return parent::destroy();
    }
}

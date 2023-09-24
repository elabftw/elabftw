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
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Services\Filter;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * The kind of items you can have in the database for a team
 * TODO permissions check
 */
class ItemsTypes extends AbstractTemplateEntity
{
    use SortableTrait;

    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->type = EntityType::ItemsTypes->value;
        $this->entityType = EntityType::ItemsTypes;
        $this->Db = Db::getConnection();
        $this->ItemsLinks = new ItemsLinks($this);
        $this->Steps = new Steps($this);
        $this->setId($id);
    }

    public function getPage(): string
    {
        return 'admin.php?tab=4&templateid=';
    }

    public function create(string $title): int
    {
        $defaultPermissions = BasePermissions::MyTeams->toJson();
        $title = Filter::title($title);
        $sql = 'INSERT INTO items_types(title, team, canread, canwrite) VALUES(:content, :team, :canread, :canwrite)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $title, PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':canread', $defaultPermissions, PDO::PARAM_STR);
        $req->bindParam(':canwrite', $defaultPermissions, PDO::PARAM_STR);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function getDefault(): ?int
    {
        return null;
    }

    /**
     * SQL to get all items type
     */
    public function readAll(): array
    {
        $sql = 'SELECT id, title, color, body, ordering, canread, canwrite
            FROM items_types WHERE team = :team AND state = :state ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT id, team, color, title, status, body, canread, canwrite, metadata, state
            FROM items_types WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        $this->entityData = $this->Db->fetch($req);
        // don't check for read permissions for items types as it can be read from many places/users
        //$this->canOrExplode('read');
        // add steps and links in there too
        $this->entityData['steps'] = $this->Steps->readAll();
        $this->entityData['items_links'] = $this->ItemsLinks->readAll();
        return $this->entityData;
    }

    public function duplicate(): int
    {
        return 1;
    }
}

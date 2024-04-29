<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\OrderingParams;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Filter;
use PDO;

/**
 * The kind of items you can have in the database for a team
 */
class ItemsTypes extends AbstractTemplateEntity
{
    public function __construct(public Users $Users, ?int $id = null)
    {
        $this->type = EntityType::ItemsTypes->value;
        $this->entityType = EntityType::ItemsTypes;
        $this->Db = Db::getConnection();
        $this->ExperimentsLinks = new ExperimentsLinks($this);
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
        $this->isAdminOrExplode();
        $defaultPermissions = BasePermissions::Team->toJson();
        $title = Filter::title($title);
        $sql = 'INSERT INTO items_types(title, team, canread, canwrite, canread_target, canwrite_target) VALUES(:content, :team, :canread, :canwrite, :canread_target, :canwrite_target)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $title, PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':canread', $defaultPermissions, PDO::PARAM_STR);
        $req->bindParam(':canwrite', $defaultPermissions, PDO::PARAM_STR);
        $req->bindParam(':canread_target', $defaultPermissions, PDO::PARAM_STR);
        $req->bindParam(':canwrite_target', $defaultPermissions, PDO::PARAM_STR);
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
        $sql = 'SELECT id, title, color, body, ordering, canread, canwrite, canread_target, canwrite_target
            FROM items_types WHERE team = :team AND state = :state ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    public function readOne(): array
    {
        $sql = 'SELECT id, team, color, title, status, body, canread, canwrite, canread_target, canwrite_target, metadata, state
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
        throw new ImproperActionException('No duplicate action for resources categories.');
    }

    /**
     * Get an id of an existing one or create it and get its id
     */
    public function getIdempotentIdFromTitle(string $title): int
    {
        $sql = 'SELECT id
            FROM items_types WHERE title = :title AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title, PDO::PARAM_STR);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch(PDO::FETCH_COLUMN);
        if (!is_int($res)) {
            return $this->create($title);
        }
        return $res;
    }

    /**
     * Use our own function instead of SortableTrait to add the team param and permission check
     */
    public function updateOrdering(OrderingParams $params): void
    {
        $this->isAdminOrExplode();
        $sql = 'UPDATE items_types SET ordering = :ordering WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        foreach ($params->ordering as $ordering => $id) {
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    private function isAdminOrExplode(): void
    {
        if ($this->bypassWritePermission === false && !$this->Users->isAdmin) {
            throw new IllegalActionException('User tried to edit items types but is not Admin');
        }
    }
}

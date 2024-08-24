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
    public EntityType $entityType = EntityType::ItemsTypes;

    public function create(string $title, ?string $color = null): int
    {
        $this->isAdminOrExplode();
        $defaultPermissions = BasePermissions::Team->toJson();
        $title = Filter::title($title);
        if ($color === null) {
            // TODO have a function for a random cool color?
            $color = '29AEB9';
        }
        $sql = 'INSERT INTO items_types(title, team, canread, canwrite, canread_target, canwrite_target, color) VALUES(:content, :team, :canread, :canwrite, :canread_target, :canwrite_target, :color)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $title);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $req->bindParam(':canread', $defaultPermissions);
        $req->bindParam(':canwrite', $defaultPermissions);
        $req->bindParam(':canread_target', $defaultPermissions);
        $req->bindParam(':canwrite_target', $defaultPermissions);
        $req->bindParam(':color', $color);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    public function getDefault(): int
    {
        // there are no default items_types, so just pick the first one from the team
        $sql = 'SELECT id FROM items_types WHERE team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
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
        $this->entityData['experiments_links'] = $this->ExperimentsLinks->readAll();
        $this->entityData['exclusive_edit_mode'] = $this->ExclusiveEditMode->readOne();
        return $this->entityData;
    }

    public function duplicate(bool $copyFiles = false): int
    {
        throw new ImproperActionException('No duplicate action for resources categories.');
    }

    /**
     * Get an id of an existing one or create it and get its id
     */
    public function getIdempotentIdFromTitle(string $title, ?string $color = null): int
    {
        $sql = 'SELECT id
            FROM items_types WHERE title = :title AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':team', $this->Users->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch(PDO::FETCH_COLUMN);
        if (!is_int($res)) {
            return $this->create($title, $color);
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

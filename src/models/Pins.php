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
use Elabftw\Elabftw\Tools;
use PDO;

/**
 * For dealing with pinned items
 */
class Pins
{
    private Db $Db;

    public function __construct(private AbstractEntity $Entity)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Check if the current entity is pin of current user
     */
    public function isPinned(): bool
    {
        $sql = 'SELECT entity_id FROM pin_' . $this->Entity->entityType->value . '2users WHERE entity_id = :entity_id AND users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        $this->Db->execute($req);
        return $req->rowCount() > 0;
    }

    /**
     * Add/remove current entity as pinned for current user
     */
    public function togglePin(): array
    {
        $this->isPinned() ? $this->rmFromPinned() : $this->addToPinned();
        return $this->Entity->readOne();
    }

    /**
     * Only read id and title to show in the create-new menu
     */
    public function readAllSimple(): array
    {
        $sql = sprintf(
            'SELECT %1$s.id FROM pin_%1$s2users LEFT JOIN %1$s ON (entity_id = %1$s.id) WHERE users_id = :users_id',
            $this->Entity->entityType->value
        );
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);

        $entity = clone $this->Entity;
        $entity->alwaysShowOwned = false;
        $entity->idFilter = Tools::getIdFilterSql(array_column($req->fetchAll(), 'id'));
        return $entity->readAll();
    }

    /**
     * Get the items pinned by current users to display in show mode
     */
    public function readAll(): array
    {
        $sql = 'SELECT entity_id FROM pin_' . $this->Entity->entityType->value . '2users WHERE users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);

        $this->Db->execute($req);

        $entity = clone $this->Entity;
        $entity->alwaysShowOwned = false;
        $entity->idFilter = Tools::getIdFilterSql(array_column($req->fetchAll(), 'entity_id'));
        return $entity->readAll();
    }

    /**
     * Remove all traces of that entity because it has been set to deleted
     */
    public function cleanup(): bool
    {
        $sql = 'DELETE FROM pin_' . $this->Entity->entityType->value . '2users WHERE entity_id = :entity_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Remove current entity from pinned of current user
     */
    private function rmFromPinned(): bool
    {
        $this->Entity->canOrExplode('read');

        $sql = 'DELETE FROM pin_' . $this->Entity->entityType->value . '2users WHERE entity_id = :entity_id AND users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Add current entity to pinned of current user
     */
    private function addToPinned(): bool
    {
        $this->Entity->canOrExplode('read');

        $sql = 'INSERT IGNORE INTO pin_' . $this->Entity->entityType->value . '2users(users_id, entity_id) VALUES (:users_id, :entity_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }
}

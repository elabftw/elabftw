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
use PDO;

/**
 * For dealing with pinned items
 */
class Pins
{
    private Db $Db;

    private AbstractEntity $Entity;

    public function __construct(AbstractEntity $entity)
    {
        $this->Db = Db::getConnection();
        $this->Entity = $entity;
    }

    /**
     * Check if the current entity is pin of current user
     */
    public function isPinned(): bool
    {
        $sql = 'SELECT DISTINCT id FROM pin2users WHERE entity_id = :entity_id AND type = :type AND users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        $this->Db->execute($req);
        return $req->rowCount() > 0;
    }

    /**
     * Add/remove current entity as pinned for current user
     */
    public function togglePin(): void
    {
        $this->isPinned() ? $this->rmFromPinned() : $this->addToPinned();
    }

    /**
     * Get the items pinned by current users to display in show mode
     */
    public function getPinned(): array
    {
        $sql = 'SELECT DISTINCT entity_id FROM pin2users WHERE users_id = :users_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':type', $this->Entity->type);

        $this->Db->execute($req);

        $pinArr = array();
        $ids = $req->fetchAll();
        if ($ids === false) {
            return $pinArr;
        }
        $entity = clone $this->Entity;
        foreach ($ids as $id) {
            $entity->setId((int) $id['entity_id']);
            $pinArr[] = $entity->read();
        }
        return $pinArr;
    }

    /**
     * Remove all traces of that entity because it has been destroyed
     */
    public function cleanup(): void
    {
        $sql = 'DELETE FROM pin2users WHERE entity_id = :entity_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        $this->Db->execute($req);
    }

    /**
     * Remove current entity from pinned of current user
     */
    private function rmFromPinned(): void
    {
        $this->Entity->canOrExplode('read');

        $sql = 'DELETE FROM pin2users WHERE entity_id = :entity_id AND users_id = :users_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        $this->Db->execute($req);
    }

    /**
     * Add current entity to pinned of current user
     */
    private function addToPinned(): void
    {
        $this->Entity->canOrExplode('read');

        $sql = 'INSERT INTO pin2users(users_id, entity_id, type) VALUES (:users_id, :entity_id, :type)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        $this->Db->execute($req);
    }
}

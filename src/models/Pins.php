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

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\Db;
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

        $ids = $this->Db->fetchAll($req);
        $entity = clone $this->Entity;

        $pinArr = array();
        foreach ($ids as $id) {
            $entity->setId((int) $id['entity_id']);
            $pinArr[] = $entity->read(new ContentParams());
        }
        return $pinArr;
    }

    /**
     * Remove all traces of that entity because it has been destroyed
     */
    public function cleanup(): bool
    {
        $sql = 'DELETE FROM pin2users WHERE entity_id = :entity_id AND type = :type';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':type', $this->Entity->type);

        return $this->Db->execute($req);
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

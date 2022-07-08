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
        $sql = 'SELECT entity_id FROM pin_' . $this->Entity->type . '2users WHERE entity_id = :entity_id AND users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        $this->Db->execute($req);
        return $req->rowCount() > 0;
    }

    /**
     * Add/remove current entity as pinned for current user
     */
    public function togglePin(): bool
    {
        return $this->isPinned() ? $this->rmFromPinned() : $this->addToPinned();
    }

    /**
     * Get the items pinned by current users to display in show mode
     */
    public function readAll(): array
    {
        $sql = 'SELECT entity_id FROM pin_' . $this->Entity->type . '2users WHERE users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);

        $this->Db->execute($req);

        $entity = clone $this->Entity;

        $pinArr = array();
        foreach ($req->fetchAll() as $id) {
            $entity->setId((int) $id['entity_id']);
            $pinArr[] = $entity->read(new ContentParams());
        }
        return $pinArr;
    }

    /**
     * Remove all traces of that entity because it has been set to deleted
     */
    public function cleanup(): bool
    {
        $sql = 'DELETE FROM pin_' . $this->Entity->type . '2users WHERE entity_id = :entity_id';
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

        $sql = 'DELETE FROM pin_' . $this->Entity->type . '2users WHERE entity_id = :entity_id AND users_id = :users_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Add current entity to pinned of current user
     */
    private function addToPinned(): bool
    {
        $this->Entity->canOrExplode('read');

        $sql = 'INSERT INTO pin_' . $this->Entity->type . '2users(users_id, entity_id) VALUES (:users_id, :entity_id)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':users_id', $this->Entity->Users->userData['userid']);
        $req->bindParam(':entity_id', $this->Entity->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }
}

<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\CleanerInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Uploads;
use PDO;

/**
 * Remove deleted items
 */
class ItemsPruner implements CleanerInterface
{
    private Db $Db;

    public function __construct()
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Remove items with deleted state from database
     * This is a global function and should only be called by items:prune command
     */
    public function cleanup(): int
    {
        $sql = 'SELECT id FROM items WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', AbstractEntity::STATE_DELETED, PDO::PARAM_INT);
        $this->Db->execute($req);
        foreach ($req->fetchAll() as $item) {
            $sql = 'DELETE FROM experiments_links WHERE link_id = :link_id';
            $req1 = $this->Db->prepare($sql);
            $req1->bindParam(':link_id', $item['id'], PDO::PARAM_INT);
            $this->Db->execute($req1);
            $sql = 'DELETE FROM items_links WHERE link_id = :link_id';
            $req2 = $this->Db->prepare($sql);
            $req2->bindParam(':link_id', $item['id'], PDO::PARAM_INT);
            $this->Db->execute($req2);
            $sql = 'DELETE FROM pin2users WHERE entity_id = :entity_id AND type = :type';
            $req3 = $this->Db->prepare($sql);
            $req3->bindParam(':entity_id', $item['id'], PDO::PARAM_INT);
            $req3->bindValue(':type', 'items');
            $this->Db->execute($req3);
            // mark all uploads related to that item as deleted
            $sql = 'UPDATE uploads SET state = :state WHERE item_id = :entity_id AND type = :type';
            $req4 = $this->Db->prepare($sql);
            $req4->bindParam(':entity_id', $item['id'], PDO::PARAM_INT);
            $req4->bindValue(':type', 'items');
            $req4->bindValue(':state', Uploads::STATE_DELETED, PDO::PARAM_INT);
            $this->Db->execute($req4);
        }
        $this->deleteFromDb();

        //

        return $req->rowCount();
    }

    private function deleteFromDb(): bool
    {
        $sql = 'DELETE FROM items WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', AbstractEntity::STATE_DELETED, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}

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
use Elabftw\Enums\State;
use Elabftw\Interfaces\CleanerInterface;
use PDO;

/**
 * Remove deleted experiments/items
 */
class EntityPruner implements CleanerInterface
{
    private Db $Db;

    public function __construct(private string $type)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Remove entity with deleted state from database
     * This is a global function and should only be called by prune:items|experiments command
     */
    public function cleanup(): int
    {
        $sql = 'SELECT id FROM ' . $this->type . ' WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
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
            $sql = 'DELETE FROM pin_' . $this->type . '2users WHERE entity_id = :entity_id';
            $req3 = $this->Db->prepare($sql);
            $req3->bindParam(':entity_id', $item['id'], PDO::PARAM_INT);
            $this->Db->execute($req3);
        }
        $this->deleteFromDb();

        return $req->rowCount();
    }

    private function deleteFromDb(): bool
    {
        $sql = 'DELETE FROM ' . $this->type . ' WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}

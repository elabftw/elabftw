<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Interfaces\CleanerInterface;
use PDO;

/**
 * Remove deleted experiments/items
 */
class EntityPruner implements CleanerInterface
{
    private Db $Db;

    public function __construct(private EntityType $entityType)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Remove entity with deleted state from database
     * This is a global function and should only be called by prune:items|experiments command
     */
    public function cleanup(): int
    {
        $sql = 'DELETE FROM ' . $this->entityType->value . ' WHERE state = :state';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->rowCount();
    }
}

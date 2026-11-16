<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Traits;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Params\OrderingParams;
use PDO;

use function array_fill;
use function array_unique;
use function count;
use function implode;
use function sprintf;

/**
 * Share updateOrdering for all things that can be reordered
 * Every user of this trait must say which column scopes the rows (team or user)
 * and which permission check applies, so a request can only reorder rows it owns.
 */
trait SortableTrait
{
    protected Db $Db;

    /**
     * Update ordering for status, categories, experiment templates or todolist items
     */
    public function updateOrdering(OrderingParams $params): void
    {
        $this->canReorderOrExplode($params);

        $table = $this->getOrderingTable();
        if ($params->table->value !== $table) {
            throw new ImproperActionException('Bad table for updateOrdering.');
        }
        $ownerColumn = $this->getOrderingOwnerColumn();
        $ownerId = $this->getOrderingOwnerId();

        // make sure every submitted id belongs to the owner before touching anything
        $ids = array_unique($params->ordering);
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $sql = sprintf('SELECT COUNT(id) FROM %s WHERE %s = ? AND id IN (%s)', $table, $ownerColumn, $placeholders);
        $req = $this->Db->prepare($sql);
        $req->bindValue(1, $ownerId, PDO::PARAM_INT);
        $position = 2;
        foreach ($ids as $id) {
            $req->bindValue($position++, $id, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        if ((int) $req->fetchColumn() !== count($ids)) {
            throw new ForbiddenException();
        }

        $sql = sprintf('UPDATE %s SET ordering = :ordering WHERE id = :id AND %s = :owner', $table, $ownerColumn);
        $req = $this->Db->prepare($sql);
        $req->bindValue(':owner', $ownerId, PDO::PARAM_INT);
        foreach ($params->ordering as $ordering => $id) {
            $req->bindValue(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindValue(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }

    /**
     * Throw if the requester is not allowed to reorder the submitted rows
     */
    abstract protected function canReorderOrExplode(OrderingParams $params): void;

    /**
     * The table holding the rows to reorder
     */
    abstract protected function getOrderingTable(): string;

    /**
     * Column restricting which rows can be reordered (e.g. team or userid)
     */
    abstract protected function getOrderingOwnerColumn(): string;

    /**
     * Value of the owner column for the requester
     */
    abstract protected function getOrderingOwnerId(): int;
}

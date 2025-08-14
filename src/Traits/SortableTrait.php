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
use Elabftw\Params\OrderingParams;
use PDO;

/**
 * Share updateOrdering for all things that can be reordered
 */
trait SortableTrait
{
    protected Db $Db;

    /**
     * Update ordering for status, experiment templates or items types
     */
    public function updateOrdering(OrderingParams $params): void
    {
        $sql = 'UPDATE ' . $params->table->value . ' SET ordering = :ordering WHERE id = :id';
        $req = $this->Db->prepare($sql);
        foreach ($params->ordering as $ordering => $id) {
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }
}

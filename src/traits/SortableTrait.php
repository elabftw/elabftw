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
use Elabftw\Elabftw\OrderingParams;
use PDO;

/**
 * Share updateOrdering for all things that can be reordered
 *
 */
trait SortableTrait
{
    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Update ordering for status, experiment templates or items types
     *
     * @param OrderingParams $params
     * @return void
     */
    public function updateOrdering(OrderingParams $params): void
    {
        foreach ($params->getOrdering() as $ordering => $id) {
            $id = explode('_', $id);
            $id = (int) $id[1];
            // the table param is whitelisted here
            $sql = 'UPDATE ' . $params->getTable() . ' SET ordering = :ordering WHERE id = :id';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':ordering', $ordering, PDO::PARAM_INT);
            $req->bindParam(':id', $id, PDO::PARAM_INT);
            $this->Db->execute($req);
        }
    }
}

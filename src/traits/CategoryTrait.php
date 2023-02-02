<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Traits;

use Elabftw\Elabftw\Db;
use PDO;

/**
 * For Status and ItemsTypes
 */
trait CategoryTrait
{
    protected Db $Db;

    // the mysql table containing entities that we can count for a given category
    protected string $countableTable;

    public function countEntities(): int
    {
        $sql = 'SELECT COUNT(id) FROM ' . $this->countableTable . ' WHERE category = :category';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }
}

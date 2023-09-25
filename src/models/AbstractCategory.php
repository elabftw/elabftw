<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Traits\EntityTrait;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * A category is a status for experiments and item type for db item
 */
abstract class AbstractCategory implements RestInterface
{
    use SortableTrait;
    use EntityTrait;

    protected Db $Db;

    protected string $table;

    public function __construct(protected Teams $Teams, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    /**
     * Get all the things
     */
    abstract public function readAll(): array;

    public function getDefault(): ?int
    {
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM ' . $this->table . ' WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $status = $req->fetchColumn();

        // if there is no is_default, null is fine
        if (!$status) {
            return null;
        }
        return (int) $status;
    }
}

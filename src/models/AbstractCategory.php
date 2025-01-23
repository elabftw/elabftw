<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Traits\EntityTrait;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * A category is a status for experiments and item type for db item
 */
abstract class AbstractCategory extends AbstractRest
{
    use SortableTrait;
    use EntityTrait;

    protected string $table;

    public function __construct(protected Teams $Teams, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    /**
     * Get an id of an existing one or create it and get its id
     */
    public function getIdempotentIdFromTitle(string $title): int
    {
        $sql = sprintf('SELECT id FROM %s WHERE title = :title AND team = :team', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch(PDO::FETCH_COLUMN);
        if (!is_int($res)) {
            return $this->postAction(Action::Create, array('name' => $title));
        }
        return $res;
    }

    public function getDefault(): ?int
    {
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM ' . $this->table . ' WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // if there is no default status, null is fine
        return (int) $req->fetchColumn() ?: null;
    }
}

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

use Elabftw\Exceptions\IllegalActionException;
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
    public function getIdempotentIdFromTitle(string $title, ?string $color = null): int
    {
        $sql = sprintf('SELECT id FROM %s WHERE title = :title AND team = :team', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch(PDO::FETCH_COLUMN);
        if (!is_int($res)) {
            return $this->create($title, $color);
        }
        return $res;
    }

    abstract public function create(string $title, ?string $color = null): int;

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

    protected function getUsersCanwriteName(): string
    {
        return $this->table;
    }

    protected function canWriteOrExplode(): void
    {
        if ($this->Teams->bypassWritePermission) {
            return;
        }
        $property = sprintf('users_canwrite_%s', $this->getUsersCanwriteName());
        if ($this->Teams->teamArr[$property] === 0 and !$this->Teams->Users->isAdmin) {
            throw new IllegalActionException();
        }
    }
}

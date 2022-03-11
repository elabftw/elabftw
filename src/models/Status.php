<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\StatusParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\StatusParamsInterface;
use PDO;

/**
 * Things related to status in admin panel
 */
class Status extends AbstractCategory
{
    public function __construct(int $team, ?int $id = null)
    {
        $this->team = $team;
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function create(StatusParamsInterface $params): int
    {
        $sql = 'INSERT INTO status(name, color, team, is_timestampable, is_default)
            VALUES(:name, :color, :team, :is_timestampable, :is_default)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent(), PDO::PARAM_STR);
        $req->bindValue(':color', $params->getColor(), PDO::PARAM_STR);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindValue(':is_timestampable', $params->getIsTimestampable(), PDO::PARAM_INT);
        $req->bindValue(':is_default', $params->getIsDefault(), PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Create a default set of status for a new team
     */
    public function createDefault(): bool
    {
        return $this->create(
            new StatusParams('Running', '#29AEB9', false, true)
        ) && $this->create(
            new StatusParams('Success', '#54AA08', true)
        ) && $this->create(
            new StatusParams('Need to be redone', '#C0C0C0', true)
        ) && $this->create(
            new StatusParams('Fail', '#C24F3D', true)
        );
    }

    public function read(ContentParamsInterface $params): array
    {
        $sql = 'SELECT id as category_id, name as category, color, is_timestampable, is_default
            FROM status WHERE team = :team AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    /**
     * SQL to get all status from team
     */
    public function readAll(): array
    {
        $sql = 'SELECT status.id AS category_id,
            status.name AS category,
            status.color,
            status.is_timestampable,
            status.is_default
            FROM status WHERE team = :team ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $req->fetchAll();
    }

    /**
     * Update a status
     */
    public function update(StatusParamsInterface $params): bool
    {
        // make sure there is only one default status
        if ($params->getIsDefault() === 1) {
            $this->setDefaultFalse();
        }

        $sql = 'UPDATE status SET
            name = :name,
            color = :color,
            is_timestampable = :is_timestampable,
            is_default = :is_default
            WHERE id = :id AND team = :team';

        $req = $this->Db->prepare($sql);
        $req->bindValue(':name', $params->getContent(), PDO::PARAM_STR);
        $req->bindValue(':color', $params->getColor(), PDO::PARAM_STR);
        $req->bindValue(':is_timestampable', $params->getIsTimestampable(), PDO::PARAM_INT);
        $req->bindValue(':is_default', $params->getIsDefault(), PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        // don't allow deletion of a status with experiments
        if ($this->countItems() > 0) {
            throw new ImproperActionException(_('Remove all experiments with this status before deleting this status.'));
        }

        $sql = 'DELETE FROM status WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Count all experiments with this status
     */
    protected function countItems(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE category = :category';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':category', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        return (int) $req->fetchColumn();
    }

    /**
     * Remove all the default status for a team.
     * If we set true to is_default somewhere, it's best to remove all other default
     * in the team so we won't have two default status
     */
    private function setDefaultFalse(): void
    {
        $sql = 'UPDATE status SET is_default = 0 WHERE team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}

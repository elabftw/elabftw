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
use Elabftw\Elabftw\StatusParams;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\StatusParamsInterface;
use Elabftw\Traits\CategoryTrait;
use PDO;

/**
 * Experiments have a Status which is expressed as a Category, the same way Items have an ItemType
 */
class Status extends AbstractCategory
{
    use CategoryTrait;

    private const DEFAULT_BLUE = '#29AEB9';

    private const DEFAULT_GREEN = '#54AA08';

    private const DEFAULT_GRAY = '#C0C0C0';

    private const DEFAULT_RED = '#C24F3D';

    public function __construct(int $team, ?int $id = null)
    {
        $this->team = $team;
        $this->countableTable = 'experiments';
        $this->Db = Db::getConnection();
        $this->id = $id;
    }

    public function create(StatusParamsInterface $params): int
    {
        $sql = 'INSERT INTO status(title, color, team, is_default)
            VALUES(:title, :color, :team, :is_default)';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':title', $params->getContent(), PDO::PARAM_STR);
        $req->bindValue(':color', $params->getColor(), PDO::PARAM_STR);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
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
            new StatusParams(_('Running'), self::DEFAULT_BLUE, true)
        ) && $this->create(
            new StatusParams(_('Success'), self::DEFAULT_GREEN)
        ) && $this->create(
            new StatusParams(_('Need to be redone'), self::DEFAULT_GRAY)
        ) && $this->create(
            new StatusParams(_('Fail'), self::DEFAULT_RED)
        );
    }

    public function read(ContentParamsInterface $params): array
    {
        if ($params->getTarget() === 'all') {
            return $this->readAll();
        }
        return $this->readOne();
    }

    /**
     * Read the current status
     */
    public function readOne(): array
    {
        $sql = 'SELECT id as category_id, title as category, color, is_default
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
            status.title AS category,
            status.color,
            status.is_default
            FROM status WHERE team = :team ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function patch(array $params): array
    {
        foreach ($params as $key => $value) {
            $this->update(new StatusParams($value, $key));
        }
        return $this->readOne();
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
            title = :title,
            color = :color,
            is_default = :is_default
            WHERE id = :id AND team = :team';

        $req = $this->Db->prepare($sql);
        $req->bindValue(':title', $params->getContent(), PDO::PARAM_STR);
        $req->bindValue(':color', $params->getColor(), PDO::PARAM_STR);
        $req->bindValue(':is_default', $params->getIsDefault(), PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function destroy(): bool
    {
        // don't allow deletion of a status with experiments
        if ($this->countEntities() > 0) {
            throw new ImproperActionException(_('Remove all experiments with this status before deleting this status.'));
        }

        $sql = 'DELETE FROM status WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->team, PDO::PARAM_INT);

        return $this->Db->execute($req);
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

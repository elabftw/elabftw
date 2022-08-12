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
use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Elabftw\Traits\CategoryTrait;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * Experiments have a Status which is expressed as a Category, the same way Items have an ItemType
 */
class Status extends AbstractCategory
{
    use CategoryTrait;
    use SetIdTrait;

    private const DEFAULT_BLUE = '29AEB9';

    private const DEFAULT_GREEN = '54AA08';

    private const DEFAULT_GRAY = 'C0C0C0';

    private const DEFAULT_RED = 'C24F3D';

    public function __construct(private Teams $Teams, ?int $id = null)
    {
        $this->countableTable = 'experiments';
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function getPage(): string
    {
        return sprintf('api/v2/teams/%d/status/', $this->Teams->id);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(
            $reqBody['name'] ?? _('Untitled'),
            $reqBody['color'] ?? '#' . self::DEFAULT_BLUE,
            $reqBody['default'] ?? 0,
        );
    }

    /**
     * Create a default set of status for a new team
     */
    public function createDefault(): bool
    {
        return $this->create(_('Running'), '#' . self::DEFAULT_BLUE, 1)
        && $this->create(_('Success'), '#' . self::DEFAULT_GREEN)
        && $this->create(_('Need to be redone'), '#' . self::DEFAULT_GRAY)
        && $this->create(_('Fail'), '#' . self::DEFAULT_RED);
    }

    public function readOne(): array
    {
        $sql = 'SELECT id as category_id, title as category, color, is_default
            FROM status WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    /**
     * Get all status from team
     */
    public function readAll(): array
    {
        $sql = 'SELECT status.id AS category_id,
            status.title AS category,
            status.color,
            status.is_default
            FROM status WHERE team = :team ORDER BY ordering ASC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function patch(array $params): array
    {
        $this->Teams->canWriteOrExplode();
        foreach ($params as $key => $value) {
            $this->update(new StatusParams($key, (string) $value));
        }
        return $this->readOne();
    }

    public function patchAction(Action $action): array
    {
        return array();
    }

    public function destroy(): bool
    {
        $this->Teams->canWriteOrExplode();
        // don't allow deletion of a status with experiments
        if ($this->countEntities() > 0) {
            throw new ImproperActionException(_('Remove all experiments with this status before deleting this status.'));
        }

        $sql = 'DELETE FROM status WHERE id = :id AND team = :team';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    private function create(string $title, string $color, int $isDefault = 0): int
    {
        $title = Filter::title($title);
        $color = Check::color($color);
        $default = Filter::toBinary($isDefault);

        $sql = 'INSERT INTO status(title, color, team, is_default)
            VALUES(:title, :color, :team, :is_default)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $title, PDO::PARAM_STR);
        $req->bindParam(':color', $color, PDO::PARAM_STR);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $req->bindParam(':is_default', $isDefault, PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    private function update(StatusParams $params): bool
    {
        // make sure there is only one default status
        if ($params->getTarget() === 'default' && $params->getContent() === 1) {
            $this->setDefaultFalse();
        }

        $sql = 'UPDATE status SET ' . $params->getColumn() . ' = :content WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':content', $params->getContent(), PDO::PARAM_STR);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
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
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}

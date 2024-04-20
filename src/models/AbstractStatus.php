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

use Elabftw\Elabftw\OrderingParams;
use Elabftw\Elabftw\StatusParams;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use PDO;

/**
 * Status for experiments or items
 */
abstract class AbstractStatus extends AbstractCategory
{
    use SetIdTrait;

    private const string DEFAULT_BLUE = '29AEB9';

    private const string DEFAULT_GREEN = '54AA08';

    private const string DEFAULT_GRAY = 'C0C0C0';

    private const string DEFAULT_RED = 'C24F3D';

    protected string $table;

    public function updateOrdering(OrderingParams $params): void
    {
        $this->Teams->canWriteOrExplode();
        parent::updateOrdering($params);
    }

    public function getPage(): string
    {
        return sprintf('api/v2/teams/%d/%s/', $this->Teams->id ?? 0, $this->table);
    }

    public function postAction(Action $action, array $reqBody): int
    {
        return $this->create(
            $reqBody['name'] ?? _('Untitled'),
            $reqBody['color'] ?? '#' . $this->getSomeColor(),
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
        $sql = sprintf('SELECT id, title, color, is_default
            FROM %s WHERE id = :id', $this->table);
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
        $sql = sprintf('SELECT id, title, color, is_default
            FROM %s WHERE team = :team AND state = :state ORDER BY ordering ASC', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    /**
     * Get all status from team independent of state
     */
    public function readAllIgnoreState(): array
    {
        $sql = sprintf('SELECT id, title, color
            FROM %s WHERE team = :team ORDER BY ordering ASC', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    public function patch(Action $action, array $params): array
    {
        $this->Teams->canWriteOrExplode();
        foreach ($params as $key => $value) {
            $this->update(new StatusParams($key, (string) $value));
        }
        return $this->readOne();
    }

    public function destroy(): bool
    {
        $this->Teams->canWriteOrExplode();
        // TODO fix FK constraints so it sets NULL instead of deleting entries
        // set state to deleted
        return $this->update(new StatusParams('state', (string) State::Deleted->value));
    }

    /**
     * Get a color that is a good for background
     */
    protected function getSomeColor(): string
    {
        $colors = array(
            self::DEFAULT_BLUE,
            self::DEFAULT_GRAY,
            self::DEFAULT_GREEN,
            self::DEFAULT_RED,
            '0A0A0A',
            '0B3D91',
            '4A3F35',
            '3D0C02',
            '253529',
            '3B3C36',
            '483C32',
            '0F4C81',
            '4B0082',
            '2F4F4F',
            '321414',
            '3C1414',
        );
        $randomKey = array_rand($colors, 1);
        return $colors[$randomKey];
    }

    private function create(string $title, string $color, int $isDefault = 0): int
    {
        $this->Teams->canWriteOrExplode();
        $title = Filter::title($title);
        $color = Check::color($color);
        $isDefault = Filter::toBinary($isDefault);

        $sql = sprintf('INSERT INTO %s (title, color, team, is_default)
            VALUES(:title, :color, :team, :is_default)', $this->table);
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
        if ($params->getTarget() === 'is_default' && $params->getContent() === 1) {
            $this->setDefaultFalse();
        }

        $sql = sprintf('UPDATE %s SET ' . $params->getColumn() . ' = :content WHERE id = :id', $this->table);
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
        $sql = sprintf('UPDATE %s SET is_default = 0 WHERE team = :team', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Teams->id, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}

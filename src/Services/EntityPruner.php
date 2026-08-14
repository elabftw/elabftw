<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CleanerInterface;
use Override;
use PDO;

use function implode;
use function preg_match;
use function strtotime;

/**
 * Remove deleted experiments/items
 */
final class EntityPruner implements CleanerInterface
{
    private Db $Db;

    public function __construct(
        private EntityType $entityType,
        private array $ids = array(),
        private array $userids = array(),
        private array $teams = array(),
        private ?string $since = null,
    ) {
        $this->Db = Db::getConnection();
    }

    /**
     * Remove entity with deleted state from database
     * This is a global function and should only be called by the prune:entries command
     */
    #[Override]
    public function cleanup(): int
    {
        $sql = 'DELETE FROM ' . $this->entityType->value . ' WHERE state = :state';

        $binds = array();
        if (!empty($this->ids)) {
            $idPlaceholders = array();
            foreach ($this->ids as $k => $id) {
                $key = ":id_$k";
                $idPlaceholders[] = $key;
                $binds[$key] = array($id, PDO::PARAM_INT);
            }
            $sql .= ' AND id IN (' . implode(',', $idPlaceholders) . ')';
        }
        if (!empty($this->userids)) {
            $userPlaceholders = array();
            foreach ($this->userids as $k => $userid) {
                $key = ":userid_$k";
                $userPlaceholders[] = $key;
                $binds[$key] = array($userid, PDO::PARAM_INT);
            }
            $sql .= ' AND userid IN (' . implode(',', $userPlaceholders) . ')';
        }
        if (!empty($this->teams)) {
            $teamPlaceholders = array();
            foreach ($this->teams as $k => $team) {
                $key = ":team_$k";
                $teamPlaceholders[] = $key;
                $binds[$key] = array($team, PDO::PARAM_INT);
            }
            $sql .= ' AND team IN (' . implode(',', $teamPlaceholders) . ')';
        }
        if ($this->since !== null) {
            $sql .= ' AND created_at >= :since';
        }

        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        foreach ($binds as $key => $bindData) {
            $req->bindValue($key, $bindData[0], $bindData[1]);
        }
        if ($this->since !== null) {
            $req->bindValue(':since', $this->parseSince($this->since));
        }
        $this->Db->execute($req);
        return $req->rowCount();
    }

    private function parseSince(string $since): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $since) === 1) {
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $since);
            $errors = DateTimeImmutable::getLastErrors();
            if ($dt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new ImproperActionException('Invalid since parameter.');
            }
            return $dt->format('Y-m-d H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $since) === 1) {
            $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $since);
            $errors = DateTimeImmutable::getLastErrors();
            if ($dt === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new ImproperActionException('Invalid since parameter.');
            }
            return $dt->format('Y-m-d H:i:s');
        }
        $unixTimestamp = strtotime($since);
        if ($unixTimestamp === false) {
            throw new ImproperActionException('Invalid since parameter.');
        }
        return (new DateTimeImmutable())->setTimestamp($unixTimestamp)->format('Y-m-d H:i:s');
    }
}

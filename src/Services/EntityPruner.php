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
use PDOStatement;
use Exception;

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
        // collect the IDs of entities that match the filter
        $selectStmt = $this->buildSelectStmt();
        $matchingIds = $selectStmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($matchingIds)) {
            return 0;
        }

        // wrap both deletes in a transaction to ensure consistency
        $this->Db->beginTransaction();
        try {
            // delete orphaned tags2entity entries before the entity rows
            $this->cleanupTags2entity($matchingIds);

            // delete the entity rows
            $deleted = $this->deleteEntities($matchingIds);

            $this->Db->commit();
            return $deleted;
        } catch (Exception $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * Build a prepared SELECT statement that returns the ids of matching deleted entities
     */
    private function buildSelectStmt(): PDOStatement
    {
        $sql = 'SELECT id FROM ' . $this->entityType->value . ' WHERE state = :state';
        $binds = array();
        $this->applyFilters($sql, $binds);
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        foreach ($binds as $key => $bindData) {
            $req->bindValue($key, $bindData[0], $bindData[1]);
        }
        if ($this->since !== null) {
            $req->bindValue(':since', $this->parseSince($this->since));
        }
        $this->Db->execute($req);
        return $req;
    }

    /**
     * Delete orphaned tag relations for the given entity ids
     */
    private function cleanupTags2entity(array $idValues): void
    {
        if (empty($idValues)) {
            return;
        }
        $placeholders = array();
        $binds = array();
        foreach ($idValues as $k => $id) {
            $key = ":id_$k";
            $placeholders[] = $key;
            $binds[$key] = array($id, PDO::PARAM_INT);
        }
        $sql = 'DELETE FROM tags2entity WHERE item_type = :item_type AND item_id IN (' . implode(',', $placeholders) . ')';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':item_type', $this->entityType->value, PDO::PARAM_STR);
        foreach ($binds as $key => $bindData) {
            $req->bindValue($key, $bindData[0], $bindData[1]);
        }
        $this->Db->execute($req);
    }

    /**
     * Delete entity rows by id
     */
    private function deleteEntities(array $idValues): int
    {
        $placeholders = array();
        $binds = array();
        foreach ($idValues as $k => $id) {
            $key = ":id_$k";
            $placeholders[] = $key;
            $binds[$key] = array($id, PDO::PARAM_INT);
        }
        $sql = 'DELETE FROM ' . $this->entityType->value . ' WHERE id IN (' . implode(',', $placeholders) . ')';
        $req = $this->Db->prepare($sql);
        foreach ($binds as $key => $bindData) {
            $req->bindValue($key, $bindData[0], $bindData[1]);
        }
        $this->Db->execute($req);
        return $req->rowCount();
    }

    /**
     * Apply the filters (ids, userids, teams, since) to a SQL query string
     */
    private function applyFilters(string &$sql, array &$binds): void
    {
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

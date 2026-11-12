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
        $this->Db->beginTransaction();
        try {
            $baseWhere = ' WHERE state = :state';
            $binds = array(':state' => array(State::Deleted->value, PDO::PARAM_INT));
            $this->applyFilters($baseWhere, $binds);
            $entityTable = $this->entityType->value;

            // delete orphaned tags2entity rows via subquery (no PHP-side ID list)
            $this->run(
                'DELETE FROM tags2entity WHERE item_type = :item_type AND item_id IN (SELECT id FROM ' . $entityTable . $baseWhere . ')',
                $binds + array(':item_type' => array($entityTable, PDO::PARAM_STR)),
            );

            // reactions are owned by their entry but only the link row cascades on entity deletion:
            // remove the reactions themselves, their components and revisions cascade from them
            $this->run('DELETE reactions FROM reactions
                JOIN reactions2' . $entityTable . ' AS links ON links.reaction_id = reactions.id
                WHERE links.entity_id IN (SELECT id FROM ' . $entityTable . $baseWhere . ')', $binds);

            // a reaction deleted from an entry that is kept is only marked as deleted, with its drawing, preview and revisions:
            // remove it too, for the entries in the scope of the filters, whatever their state
            $scopeWhere = ' WHERE 1=1';
            $scopeBinds = array(':reaction_state' => array(State::Deleted->value, PDO::PARAM_INT));
            $this->applyFilters($scopeWhere, $scopeBinds);
            $this->run('DELETE reactions FROM reactions
                JOIN reactions2' . $entityTable . ' AS links ON links.reaction_id = reactions.id
                WHERE reactions.state = :reaction_state
                AND links.entity_id IN (SELECT id FROM ' . $entityTable . $scopeWhere . ')', $scopeBinds);

            // delete the entity rows (same WHERE, so restored entities are skipped)
            $deleted = $this->run('DELETE FROM ' . $entityTable . $baseWhere, $binds)->rowCount();

            $this->Db->commit();
            return $deleted;
        } catch (Exception $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * Apply the filters (ids, userids, teams, since) to a SQL query string, and add their values to the binds
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
            $binds[':since'] = array($this->parseSince($this->since), PDO::PARAM_STR);
        }
    }

    /**
     * @param array<string, array{0: mixed, 1: int}> $binds the values of the placeholders, with their type
     */
    private function run(string $sql, array $binds): PDOStatement
    {
        $req = $this->Db->prepare($sql);
        foreach ($binds as $key => $bindData) {
            $req->bindValue($key, $bindData[0], $bindData[1]);
        }
        $this->Db->execute($req);
        return $req;
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

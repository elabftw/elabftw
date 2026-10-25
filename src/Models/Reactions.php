<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Enums\AccessType;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Params\ContentParams;
use Elabftw\Params\ReactionParams;
use Elabftw\Services\ReactionCalculator;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;
use Throwable;

use function array_keys;
use function array_map;
use function array_merge;
use function array_replace;
use function count;
use function filter_var;
use function htmlspecialchars;
use function implode;
use function json_decode;
use function json_encode;
use function sprintf;
use function array_column;

/**
 * An entry owns its reaction records. There is deliberately no endpoint to link
 * an existing mutable reaction to another entry: duplication creates a copy.
 */
final class Reactions extends AbstractRest
{
    use SetIdTrait;

    public function __construct(private AbstractEntity $Entity, ?int $id = null)
    {
        parent::__construct();
        $this->setId($id);
    }

    #[Override]
    public function getApiPath(): string
    {
        return sprintf('%s%d/reactions/', $this->Entity->getApiPath(), $this->Entity->id ?? 0);
    }

    #[Override]
    public function readAll(?QueryParamsInterface $queryParams = null): array
    {
        $this->Entity->canOrExplode(AccessType::Read);
        if ($queryParams !== null && $queryParams->getQuery()->getInt('history_of') > 0) {
            return $this->readHistory($queryParams->getQuery()->getInt('history_of'));
        }
        $req = $this->Db->prepare('SELECT r.* FROM reactions r JOIN ' . $this->linkTable() . ' l ON l.reaction_id = r.id
            WHERE l.entity_id = :entity_id AND r.state = :state ORDER BY l.ordering, r.id');
        $req->bindValue(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $reactions = $req->fetchAll();
        if ($reactions === array()) {
            return array();
        }
        $ids = implode(',', array_map(static fn(array $r): int => (int) $r['id'], $reactions));
        $req = $this->Db->q('SELECT * FROM reaction_components WHERE reaction_id IN (' . $ids . ') ORDER BY ordering, id');
        $components = array();
        foreach ($req->fetchAll() as $component) {
            $components[$component['reaction_id']][] = $component;
        }
        return array_map(fn(array $r): array => $this->hydrate($r, $components[$r['id']] ?? array()), $reactions);
    }

    #[Override]
    public function readOne(): array
    {
        $this->Entity->canOrExplode(AccessType::Read);
        $reaction = $this->readRow($this->id ?? 0);
        $req = $this->Db->prepare('SELECT * FROM reaction_components WHERE reaction_id = :id ORDER BY ordering, id');
        $req->bindValue(':id', $reaction['id'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->hydrate($reaction, $req->fetchAll());
    }

    #[Override]
    public function postAction(Action $action, array $reqBody): int
    {
        if ($action !== Action::Create || $this->id !== null) {
            throw new ImproperActionException('Invalid action for reactions.');
        }
        return $this->save(new ReactionParams($reqBody));
    }

    #[Override]
    public function patch(Action $action, array $params): array
    {
        if ($action === Action::Calculate && $this->id === null) {
            $this->Entity->canOrExplode(AccessType::Write);
            $validated = new ReactionParams($params);
            return array('components' => new ReactionCalculator()->calculate($validated->reaction, $validated->components));
        }
        if ($action !== Action::Update || $this->id === null) {
            throw new ImproperActionException('Invalid action for reactions.');
        }
        // Partial PATCH keeps omitted fields, but a supplied component list is a
        // complete replacement. Optimistic locking prevents stale table saves.
        $current = $this->readOne();
        $expected = $params['version'] ?? null;
        if (filter_var($expected, FILTER_VALIDATE_INT) === false || (int) $expected !== (int) $current['version']) {
            throw new ImproperActionException('This reaction has changed. Reload it before saving.');
        }
        $this->save(new ReactionParams(array_replace($current, $params)), (int) $expected, $current['components']);
        return $this->readOne();
    }

    #[Override]
    public function destroy(bool $recursive = false): bool
    {
        $this->Db->beginTransaction();
        try {
            $this->lockOwner();
            $current = $this->readOne();
            $req = $this->Db->prepare('UPDATE reactions SET state = :state, version = version + 1, modified_by = :userid WHERE id = :id');
            $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
            $req->bindValue(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindValue(':id', $this->id, PDO::PARAM_INT);
            $this->Db->execute($req);
            $deleted = array_replace($current, $this->readRow($this->id ?? 0, true));
            $deleted['scheme'] = $current['scheme'];
            $this->recordChange($deleted, 'Deleted');
            $this->Db->commit();
            return true;
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    public function duplicate(AbstractEntity $target): void
    {
        foreach ($this->readAll() as $reaction) {
            // Reading the source already authorized access to these saved links.
            new self($target)->save(new ReactionParams($reaction), null, $reaction['components']);
        }
    }

    public function readAllHistory(): array
    {
        $this->Entity->canOrExplode(AccessType::Read);
        $req = $this->Db->prepare('SELECT h.snapshot FROM reaction_revisions h JOIN ' . $this->linkTable() . ' l ON l.reaction_id = h.reaction_id
            WHERE l.entity_id = :id ORDER BY h.reaction_id, h.version');
        $req->bindValue(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_map(static fn(array $row): array => json_decode($row['snapshot'], true, 512, JSON_THROW_ON_ERROR), $req->fetchAll());
    }

    /** Import foreign snapshots without retaining installation-local links. */
    public function import(array $reaction): int
    {
        $components = $reaction['components'] ?? array();
        foreach ($components as &$component) {
            $component['compound_id'] = null;
            $component['item_id'] = null;
        }
        unset($component);
        $reaction['components'] = $components;
        return $this->postAction(Action::Create, $reaction);
    }

    private function save(ReactionParams $params, ?int $expectedVersion = null, array $existingComponents = array()): int
    {
        $this->Db->beginTransaction();
        try {
            $this->lockOwner();
            $this->checkLinks($params->components, $existingComponents);
            $fields = $params->reaction;
            $fields['modified_by'] = $this->Entity->Users->userData['userid'];
            $creating = $this->id === null;
            if ($creating) {
                $fields['created_by'] = $fields['modified_by'];
                $id = $this->insert('reactions', $fields);
                $this->insert($this->linkTable(), array('reaction_id' => $id, 'entity_id' => $this->Entity->id, 'ordering' => count($this->readAll())));
                $this->setId($id);
            } else {
                $current = $this->readRow($this->id ?? throw new ImproperActionException('Missing reaction id.'));
                if ((int) $current['version'] !== $expectedVersion) {
                    throw new ImproperActionException('This reaction has changed. Reload it before saving.');
                }
                $assignments = implode(', ', array_map(static fn(string $field): string => $field . ' = :' . $field, array_keys($fields)));
                $req = $this->Db->prepare('UPDATE reactions SET ' . $assignments . ', version = version + 1 WHERE id = :id');
                foreach ($fields as $field => $value) {
                    $req->bindValue(':' . $field, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                }
                $req->bindValue(':id', $this->id, PDO::PARAM_INT);
                $this->Db->execute($req);
                $req = $this->Db->prepare('DELETE FROM reaction_components WHERE reaction_id = :id');
                $req->bindValue(':id', $this->id, PDO::PARAM_INT);
                $this->Db->execute($req);
            }
            foreach ($params->components as $ordering => $component) {
                $this->insert('reaction_components', array_merge($component, array('reaction_id' => $this->id, 'ordering' => $ordering)));
            }
            $reaction = $this->readOne();
            $this->recordChange($reaction, $creating ? 'Created' : 'Updated');
            $this->Db->commit();
            return $this->id ?? throw new ImproperActionException('Missing reaction id.');
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    private function lockOwner(): void
    {
        // Serialize changes with other reaction saves and entry locking.
        $req = $this->Db->prepare('SELECT id FROM ' . $this->Entity->entityType->value . ' WHERE id = :id FOR UPDATE');
        $req->bindValue(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->Db->fetch($req);
        $this->Entity->readOne();
        $this->Entity->canOrExplode(AccessType::Write);
        if ($this->Entity->isReadOnly && !$this->Entity->bypassWritePermission) {
            throw new ForbiddenException();
        }
    }

    private function readRow(int $id, bool $includeDeleted = false): array
    {
        $req = $this->Db->prepare('SELECT r.* FROM reactions r JOIN ' . $this->linkTable() . ' l ON l.reaction_id = r.id
            WHERE r.id = :id AND l.entity_id = :entity_id' . ($includeDeleted ? '' : ' AND r.state = 1'));
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $req->bindValue(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    private function checkLinks(array $components, array $existingComponents): void
    {
        $existingItems = array_column($existingComponents, 'item_id', 'component_key');
        foreach ($components as $component) {
            if ($component['compound_id'] !== null) {
                $req = $this->Db->prepare('SELECT inchi_key FROM compounds WHERE id = :id AND state IN (1, 2)');
                $req->bindValue(':id', $component['compound_id'], PDO::PARAM_INT);
                $this->Db->execute($req);
                $compound = $this->Db->fetch($req);
                if ($component['inchi_key'] !== '' && !empty($compound['inchi_key']) && $component['inchi_key'] !== $compound['inchi_key']) {
                    throw new ImproperActionException('The linked compound does not match this reaction component.');
                }
            }
            if ($component['item_id'] !== null && $component['item_id'] !== ($existingItems[$component['component_key']] ?? null)) {
                new Items($this->Entity->Users, $component['item_id'])->readOne();
            }
        }
    }

    private function hydrate(array $reaction, array $components): array
    {
        $reaction['scheme'] = $reaction['scheme'] === null ? null : json_decode($reaction['scheme'], true, 512, JSON_THROW_ON_ERROR);
        $reaction['components'] = new ReactionCalculator()->calculate($reaction, $components);
        return $reaction;
    }

    private function recordChange(array $reaction, string $verb): void
    {
        $this->insert('reaction_revisions', array(
            'reaction_id' => $reaction['id'],
            'version' => $reaction['version'],
            'snapshot' => json_encode($reaction, JSON_THROW_ON_ERROR),
            'created_by' => $this->Entity->Users->userData['userid'],
        ));
        $this->Entity->touch();
        new Changelog($this->Entity)->create(new ContentParams('reactions', sprintf('%s reaction "%s" with id: %d', $verb, htmlspecialchars($reaction['title'], ENT_QUOTES), $this->id ?? 0)));
    }

    private function readHistory(int $id): array
    {
        $this->readRow($id, true);
        $req = $this->Db->prepare('SELECT version, created_at, created_by, snapshot FROM reaction_revisions WHERE reaction_id = :id ORDER BY version');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return array_map(static function (array $row): array {
            $row['snapshot'] = json_decode($row['snapshot'], true, 512, JSON_THROW_ON_ERROR);
            return $row;
        }, $req->fetchAll());
    }

    /** Only called with internal table/column names, never request keys. */
    private function insert(string $table, array $fields): int
    {
        $columns = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_map(static fn(string $name): string => ':' . $name, array_keys($fields)));
        $req = $this->Db->prepare('INSERT INTO ' . $table . ' (' . $columns . ') VALUES (' . $placeholders . ')');
        foreach ($fields as $name => $value) {
            $req->bindValue(':' . $name, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    private function linkTable(): string
    {
        return 'reactions2' . $this->Entity->entityType->value;
    }
}

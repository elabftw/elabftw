<?php

/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models;

use Elabftw\Elabftw\CompoundPermissions;
use Elabftw\Elabftw\Permissions;
use Elabftw\Enums\AccessType;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Action;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\FingerprinterInterface;
use Elabftw\Interfaces\QueryParamsInterface;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Params\ContentParams;
use Elabftw\Params\ReactionParams;
use Elabftw\Services\OpenBabelFingerprinter;
use Elabftw\Services\ReactionCalculator;
use Elabftw\Services\ReactionFingerprinter;
use Elabftw\Services\ReactionIdentity;
use Elabftw\Traits\SetIdTrait;
use Override;
use PDO;
use PDOStatement;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_replace;
use function base64_decode;
use function filter_var;
use function implode;
use function in_array;
use function is_array;
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

    /** Entered or derived on save: a copy with these is the same reaction */
    private const array CONTENT_COLUMNS = array(
        'title', 'scheme', 'rxn', 'reaction_smiles', 'reaction_key', 'preview_png', 'temperature_c', 'duration_s', 'pressure_pa',
        'atmosphere', 'conditions_notes', 'reference_component_key', 'yield_basis_component_key',
    );

    private const array COMPONENT_COLUMNS = array(
        'component_key', 'ordering', 'role', 'name', 'molfile', 'smiles', 'inchi_key', 'molecular_formula', 'molecular_weight',
        'compound_id', 'item_id', 'material_form', 'purity_percent', 'density_g_ml', 'concentration_mol_l', 'coefficient',
        'planned_basis', 'planned_unit', 'planned_value', 'actual_basis', 'actual_unit', 'actual_value',
    );

    /** Only needed to draw, edit or export a reaction, not to display it with its entry */
    private const array LARGE_COLUMNS = array('scheme', 'rxn', 'preview_png');

    private const array LARGE_COMPONENT_COLUMNS = array('molfile');

    /** What the write permission check of the entry needs, see lockOwner() */
    private const string OWNER_COLUMNS = 'userid, team, state, locked, lockedby, canread_base, canwrite_base, canread, canwrite';

    public function __construct(
        private AbstractEntity $Entity,
        ?int $id = null,
        private FingerprinterInterface $fingerprinter = new OpenBabelFingerprinter(),
    ) {
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
        return $this->readFull();
    }

    /**
     * The current reactions with everything needed to draw, edit or export them.
     * Like readSummaries(), it is for callers that already checked Read access on the entity.
     */
    public function readFull(): array
    {
        return $this->readCurrent('r.*', '*');
    }

    /**
     * The current reactions without their large columns (nor the molfile of their components): what an entry needs
     * to display them. has_preview tells if the preview can be loaded with readBinary().
     */
    public function readSummaries(): array
    {
        return $this->readCurrent(
            'r.id, r.version, r.state, r.created_at, r.modified_at, r.created_by, r.modified_by, '
                . self::columns(array_diff(self::CONTENT_COLUMNS, self::LARGE_COLUMNS), 'r')
                . ", r.preview_png != '' AS has_preview",
            'id, reaction_id, ' . self::columns(array_diff(self::COMPONENT_COLUMNS, self::LARGE_COMPONENT_COLUMNS)),
        );
    }

    /**
     * Give the summaries of readSummaries() their preview inline (base64 PNG), for a page that cannot load them from the API:
     * opened with the access key of the entry, the request has no session the API would accept. Like readSummaries(),
     * it is for callers that already checked Read access on the entity.
     */
    public function addPreviews(array $summaries): array
    {
        $req = $this->Db->prepare('SELECT r.id, r.preview_png FROM reactions r JOIN ' . $this->linkTable() . " l ON l.reaction_id = r.id
            WHERE l.entity_id = :entity_id AND r.state = :state AND r.preview_png != ''");
        $req->bindValue(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $previews = array_column($req->fetchAll(), 'preview_png', 'id');
        return array_map(static function (array $summary) use ($previews): array {
            if (isset($previews[$summary['id']])) {
                $summary['preview_png'] = $previews[$summary['id']];
            }
            return $summary;
        }, $summaries);
    }

    #[Override]
    public function readOne(): array
    {
        $this->Entity->canOrExplode(AccessType::Read);
        $reaction = $this->readRow($this->id ?? 0);
        return $this->hydrate($reaction, $this->readComponents((int) $reaction['id']));
    }

    /** The preview image is served on its own, so an entry does not carry the previews of its reactions */
    public function readBinary(): Response
    {
        $this->Entity->canOrExplode(AccessType::Read);
        $reaction = $this->readRow($this->id ?? 0, columns: 'r.id, r.version, r.preview_png');
        $png = base64_decode($reaction['preview_png'], true);
        if ($png === false || $png === '') {
            throw new ResourceNotFoundException();
        }
        return new Response($png, Response::HTTP_OK, array(
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-cache',
            // a saved change bumps the version, so the browser only downloads a preview again after a change
            'ETag' => sprintf('"reaction-%d-%d"', $reaction['id'], $reaction['version']),
        ));
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
            // only a current reaction can be deleted
            $this->readRow($this->id ?? 0, columns: 'r.id');
            $req = $this->Db->prepare('UPDATE reactions SET state = :state, version = version + 1, modified_by = :userid WHERE id = :id');
            $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
            $req->bindValue(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
            $req->bindValue(':id', $this->id, PDO::PARAM_INT);
            $this->Db->execute($req);
            $this->recordChange('Deleted', withDrawing: false);
            $this->Db->commit();
            return true;
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * Copied in SQL rather than saved again: the source was validated when it was saved, with its links and fingerprints,
     * so copying an entry does not wait for Open Babel or check links again (anyone who can read the source can see them).
     */
    public function duplicate(AbstractEntity $target): void
    {
        $this->Entity->canOrExplode(AccessType::Read);
        $target->canOrExplode(AccessType::Write);
        $this->Db->beginTransaction();
        try {
            foreach ($this->readCurrent('r.id', null) as $reaction) {
                new self($target, null, $this->fingerprinter)->copyFrom((int) $reaction['id']);
            }
            $this->Db->commit();
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    public function readAllHistory(): array
    {
        $this->canReadHistoryOrExplode();
        $req = $this->Db->prepare('SELECT h.snapshot FROM reaction_revisions h JOIN ' . $this->linkTable() . ' l ON l.reaction_id = h.reaction_id
            WHERE l.entity_id = :id ORDER BY h.reaction_id, h.version');
        $req->bindValue(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return self::expandHistory(array_map(static fn(array $row): array => self::decode($row['snapshot']), $req->fetchAll()));
    }

    /** Import foreign snapshots without retaining installation-local links. */
    public function import(array $reaction): int
    {
        $components = $reaction['components'] ?? array();
        // malformed components are left as is for ReactionParams to reject
        if (is_array($components)) {
            foreach ($components as &$component) {
                if (is_array($component)) {
                    $component['compound_id'] = null;
                    $component['item_id'] = null;
                }
            }
            unset($component);
        }
        $reaction['components'] = $components;
        return $this->postAction(Action::Create, $reaction);
    }

    private function save(ReactionParams $params, ?int $expectedVersion = null, array $existingComponents = array()): int
    {
        // fingerprinting runs external processes: do it before taking the entry lock,
        // but only for someone allowed to write (lockOwner() checks again under the lock)
        $this->Entity->canOrExplode(AccessType::Write);
        // an unchanged structure keeps its fingerprint: editing a title or a quantity must not run Open Babel again
        $fingerprints = $this->reusableFingerprints($params->components, $existingComponents);
        $fingerprints += new ReactionFingerprinter($this->fingerprinter)->calculate(
            array_filter($params->components, static fn(array $component): bool => !isset($fingerprints[$component['component_key']])),
        );
        $this->Db->beginTransaction();
        try {
            $this->lockOwner();
            $this->checkLinks($params->components, $existingComponents);
            $fields = $params->reaction;
            $fields['reaction_smiles'] = ReactionIdentity::smiles($params->components);
            $fields['reaction_key'] = ReactionIdentity::key($params->components);
            $fields['modified_by'] = $this->Entity->Users->userData['userid'];
            $creating = $this->id === null;
            $drawingChanged = true;
            if ($creating) {
                $fields['created_by'] = $fields['modified_by'];
                $id = $this->insert('reactions', $fields);
                $this->insert($this->linkTable(), array('reaction_id' => $id, 'entity_id' => $this->Entity->id, 'ordering' => $this->getNextOrdering()));
                $this->setId($id);
            } else {
                $current = $this->readRow($this->id ?? throw new ImproperActionException('Missing reaction id.'));
                if ((int) $current['version'] !== $expectedVersion) {
                    throw new ImproperActionException('This reaction has changed. Reload it before saving.');
                }
                // the JSON column does not give back the text it was given: compare the documents
                $drawingChanged = $current['rxn'] !== $fields['rxn'] || self::decodeScheme($current['scheme']) != self::decodeScheme($fields['scheme']);
                $assignments = implode(', ', array_map(static fn(string $field): string => $field . ' = :' . $field, array_keys($fields)));
                $req = $this->Db->prepare('UPDATE reactions SET ' . $assignments . ', version = version + 1 WHERE id = :id');
                $this->bindFields($req, $fields);
                $req->bindValue(':id', $this->id, PDO::PARAM_INT);
                $this->Db->execute($req);
                $req = $this->Db->prepare('DELETE FROM reaction_components WHERE reaction_id = :id');
                $req->bindValue(':id', $this->id, PDO::PARAM_INT);
                $this->Db->execute($req);
            }
            // replaced components took their fingerprints with them (ON DELETE CASCADE)
            foreach ($params->components as $ordering => $component) {
                $componentId = $this->insert('reaction_components', array_merge($component, array('reaction_id' => $this->id, 'ordering' => $ordering)));
                if (isset($fingerprints[$component['component_key']])) {
                    new ReactionComponentFingerprints()->upsert($componentId, $fingerprints[$component['component_key']]);
                }
            }
            $this->recordChange($creating ? 'Created' : 'Updated', $drawingChanged);
            $this->Db->commit();
            return $this->id ?? throw new ImproperActionException('Missing reaction id.');
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * Fingerprints of the saved components that keep their key and structure, by component key.
     * The existing components were read before the lock, but save() rejects them if the version changed since.
     *
     * @return array<string, list<int>>
     */
    private function reusableFingerprints(array $components, array $existingComponents): array
    {
        $existing = array();
        foreach ($existingComponents as $component) {
            $existing[$component['component_key']] = $component;
        }
        $ids = array();
        foreach ($components as $component) {
            $previous = $existing[$component['component_key']] ?? null;
            if ($previous !== null && ReactionIdentity::normalizeSmiles((string) $previous['smiles']) === ReactionIdentity::normalizeSmiles($component['smiles'])) {
                $ids[(int) $previous['id']] = $component['component_key'];
            }
        }
        $fingerprints = array();
        foreach (new ReactionComponentFingerprints()->readMany(array_keys($ids)) as $componentId => $fingerprint) {
            $fingerprints[$ids[$componentId]] = $fingerprint;
        }
        return $fingerprints;
    }

    /**
     * Serialize changes with other reaction saves and entry locking, and check the write access on the entry row as it is now.
     * Only the columns of the permission check are read again under the lock, not the whole entry (see AbstractEntity::readOne()).
     */
    private function lockOwner(): void
    {
        $req = $this->Db->prepare(sprintf('SELECT %s FROM %s WHERE id = :id FOR UPDATE', self::OWNER_COLUMNS, $this->Entity->entityType->value));
        $req->bindValue(':id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->Entity->entityData = array_replace($this->Entity->entityData, $this->Db->fetch($req));
        $this->Entity->canOrExplode(AccessType::Write);
        if (($this->Entity->isReadOnly || $this->Entity->ExclusiveEditMode->isActive()) && !$this->Entity->bypassWritePermission) {
            throw new ForbiddenException();
        }
    }

    /** Deleted reactions keep their link row: append after them instead of counting current reactions. */
    private function getNextOrdering(): int
    {
        $req = $this->Db->prepare('SELECT COALESCE(MAX(ordering), -1) + 1 FROM ' . $this->linkTable() . ' WHERE entity_id = :entity_id');
        $req->bindValue(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /** Like entry revisions, reaction history is not available to anonymous users. */
    private function canReadHistoryOrExplode(): void
    {
        if ($this->Entity->Users instanceof AnonymousUser) {
            throw new ForbiddenException();
        }
        $this->Entity->canOrExplode(AccessType::Read);
    }

    private function readRow(int $id, bool $includeDeleted = false, string $columns = 'r.*'): array
    {
        $req = $this->Db->prepare('SELECT ' . $columns . ' FROM reactions r JOIN ' . $this->linkTable() . ' l ON l.reaction_id = r.id
            WHERE r.id = :id AND l.entity_id = :entity_id' . ($includeDeleted ? '' : ' AND r.state = 1'));
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $req->bindValue(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    private function checkLinks(array $components, array $existingComponents): void
    {
        $existingItems = array_column($existingComponents, 'item_id', 'component_key');
        $existingCompounds = array_column($existingComponents, 'compound_id', 'component_key');
        $checkedItems = array();
        foreach ($components as $component) {
            if ($component['compound_id'] !== null) {
                // like a resource, a compound can only be linked by someone who can read it: a kept link is not checked again
                $compound = $component['compound_id'] === ($existingCompounds[$component['component_key']] ?? null)
                    ? $this->readCompound($component['compound_id'])
                    : new CompoundPermissions($this->Entity->Users)->canOrExplodeFromId($component['compound_id'], AccessType::Read);
                if (!in_array((int) $compound['state'], array(State::Normal->value, State::Archived->value), true)) {
                    throw new ResourceNotFoundException();
                }
                if ($component['inchi_key'] !== '' && !empty($compound['inchi_key']) && $component['inchi_key'] !== $compound['inchi_key']) {
                    throw new ImproperActionException('The linked compound does not match this reaction component.');
                }
            }
            $itemId = $component['item_id'];
            if ($itemId !== null && $itemId !== ($existingItems[$component['component_key']] ?? null) && !isset($checkedItems[$itemId])) {
                $this->canReadItemOrExplode($itemId);
                $checkedItems[$itemId] = true;
            }
        }
    }

    private function readCompound(int $id): array
    {
        $req = $this->Db->prepare('SELECT state, inchi_key FROM compounds WHERE id = :id');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $this->Db->fetch($req);
    }

    /**
     * Same answer as Items::readOne() for the read permission, from the item row alone:
     * a full read would also load the steps, links, uploads, comments and reactions of the item, for nothing.
     */
    private function canReadItemOrExplode(int $itemId): void
    {
        $req = $this->Db->prepare('SELECT team, userid, canread, canread_base, canwrite, canwrite_base FROM items WHERE id = :id');
        $req->bindValue(':id', $itemId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $item = $this->Db->fetch($req);
        $canread = json_decode($item['canread'], true, 512, JSON_THROW_ON_ERROR);
        if (!new Permissions($this->Entity->Users, $item)->getCan(BasePermissions::from($item['canread_base']), $canread)) {
            throw new ForbiddenException();
        }
    }

    private static function decode(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private static function decodeScheme(?string $scheme): ?array
    {
        return $scheme === null ? null : self::decode($scheme);
    }

    private static function columns(array $names, string $alias = ''): string
    {
        return implode(', ', array_map(static fn(string $name): string => ($alias === '' ? '' : $alias . '.') . $name, $names));
    }

    /** Current (not deleted) reactions of the entry, in their order. Without component columns, components are not read. */
    private function readCurrent(string $columns, ?string $componentColumns): array
    {
        $req = $this->Db->prepare('SELECT ' . $columns . ' FROM reactions r JOIN ' . $this->linkTable() . ' l ON l.reaction_id = r.id
            WHERE l.entity_id = :entity_id AND r.state = :state ORDER BY l.ordering, r.id');
        $req->bindValue(':entity_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $this->Db->execute($req);
        $reactions = $req->fetchAll();
        if ($componentColumns === null || $reactions === array()) {
            return $reactions;
        }
        $ids = implode(',', array_map(static fn(array $r): int => (int) $r['id'], $reactions));
        $req = $this->Db->q('SELECT ' . $componentColumns . ' FROM reaction_components WHERE reaction_id IN (' . $ids . ') ORDER BY ordering, id');
        $components = array();
        foreach ($req->fetchAll() as $component) {
            $components[$component['reaction_id']][] = $component;
        }
        return array_map(fn(array $r): array => $this->hydrate($r, $components[$r['id']] ?? array()), $reactions);
    }

    private function readComponents(int $reactionId): array
    {
        $req = $this->Db->prepare('SELECT * FROM reaction_components WHERE reaction_id = :id ORDER BY ordering, id');
        $req->bindValue(':id', $reactionId, PDO::PARAM_INT);
        $this->Db->execute($req);
        return $req->fetchAll();
    }

    private function hydrate(array $reaction, array $components): array
    {
        if (array_key_exists('scheme', $reaction)) {
            $reaction['scheme'] = self::decodeScheme($reaction['scheme']);
        }
        $reaction['components'] = new ReactionCalculator()->calculate($reaction, $components);
        return $reaction;
    }

    /** This instance is the copy: a new reaction of its entity, made from the source reaction */
    private function copyFrom(int $sourceId): void
    {
        $userid = $this->Entity->Users->userData['userid'];
        $req = $this->Db->prepare(sprintf(
            'INSERT INTO reactions (%1$s, created_by, modified_by) SELECT %1$s, :created_by, :modified_by FROM reactions WHERE id = :source',
            self::columns(self::CONTENT_COLUMNS),
        ));
        $req->bindValue(':created_by', $userid, PDO::PARAM_INT);
        $req->bindValue(':modified_by', $userid, PDO::PARAM_INT);
        $req->bindValue(':source', $sourceId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $this->setId($this->Db->lastInsertId());
        $this->insert($this->linkTable(), array('reaction_id' => $this->id, 'entity_id' => $this->Entity->id, 'ordering' => $this->getNextOrdering()));

        $req = $this->Db->prepare(sprintf(
            'INSERT INTO reaction_components (reaction_id, %1$s) SELECT :copy, %1$s FROM reaction_components WHERE reaction_id = :source',
            self::columns(self::COMPONENT_COLUMNS),
        ));
        $req->bindValue(':copy', $this->id, PDO::PARAM_INT);
        $req->bindValue(':source', $sourceId, PDO::PARAM_INT);
        $this->Db->execute($req);
        new ReactionComponentFingerprints()->copy($sourceId, $this->id ?? 0);
        $this->recordChange('Created', withDrawing: true);
    }

    /**
     * A revision keeps the reaction as stored, but not what is derived from it: the preview renders the scheme,
     * and the quantities are calculated again when reading the history. The drawing is only kept when it changed,
     * see expandHistory().
     */
    private function recordChange(string $verb, bool $withDrawing): void
    {
        $snapshot = $this->readRow($this->id ?? 0, true);
        unset($snapshot['preview_png']);
        if ($withDrawing) {
            $snapshot['scheme'] = self::decodeScheme($snapshot['scheme']);
        } else {
            unset($snapshot['scheme'], $snapshot['rxn']);
        }
        $snapshot['components'] = $this->readComponents((int) $snapshot['id']);
        $this->insert('reaction_revisions', array(
            'reaction_id' => $snapshot['id'],
            'version' => $snapshot['version'],
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_by' => $this->Entity->Users->userData['userid'],
        ));
        $this->Entity->touch();
        // the changelog table escapes this target when it is displayed
        new Changelog($this->Entity)->create(new ContentParams('reactions', sprintf('%s reaction "%s" with id: %d', $verb, $snapshot['title'], $this->id ?? 0)));
    }

    /**
     * Give each revision the drawing it had (the last one stored for its reaction) and its calculated quantities.
     * The snapshots are in version order for each reaction.
     */
    private static function expandHistory(array $snapshots): array
    {
        $drawings = array();
        return array_map(static function (array $snapshot) use (&$drawings): array {
            if (array_key_exists('rxn', $snapshot)) {
                $drawings[$snapshot['id']] = array('scheme' => $snapshot['scheme'], 'rxn' => $snapshot['rxn']);
            }
            $snapshot += $drawings[$snapshot['id']] ?? array();
            $snapshot['components'] = new ReactionCalculator()->calculate($snapshot, $snapshot['components']);
            return $snapshot;
        }, $snapshots);
    }

    private function readHistory(int $id): array
    {
        $this->canReadHistoryOrExplode();
        $this->readRow($id, true, 'r.id');
        $req = $this->Db->prepare('SELECT version, created_at, created_by, snapshot FROM reaction_revisions WHERE reaction_id = :id ORDER BY version');
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $rows = $req->fetchAll();
        $snapshots = self::expandHistory(array_map(static fn(array $row): array => self::decode($row['snapshot']), $rows));
        return array_map(
            static fn(array $row, array $snapshot): array => array_replace($row, array('snapshot' => $snapshot)),
            $rows,
            $snapshots,
        );
    }

    /** Only called with internal table/column names, never request keys. */
    private function insert(string $table, array $fields): int
    {
        $columns = array_keys($fields);
        $placeholders = array_map(static fn(string $name): string => ':' . $name, $columns);
        $req = $this->Db->prepare(sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), implode(', ', $placeholders)));
        $this->bindFields($req, $fields);
        $this->Db->execute($req);
        return $this->Db->lastInsertId();
    }

    /** Placeholders are named after the columns, see insert() and save() */
    private function bindFields(PDOStatement $req, array $fields): void
    {
        foreach ($fields as $name => $value) {
            $req->bindValue(':' . $name, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
    }

    private function linkTable(): string
    {
        return 'reactions2' . $this->Entity->entityType->value;
    }
}

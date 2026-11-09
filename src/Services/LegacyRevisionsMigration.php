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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\DataMigrationInterface;
use Override;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function abs;
use function count;
use function hash;
use function implode;
use function sprintf;

/**
 * Move the revisions of the legacy *_revisions tables to the *_body_revisions tables, and back
 *
 * The body, content type, author and date of every legacy revision are kept. Their id changes, and their
 * modified_at is not kept: it was never changed after creation. Revisions pointing to an entry that does
 * not exist anymore (old installs without foreign keys) cannot be shown anywhere and are left out.
 */
final class LegacyRevisionsMigration implements DataMigrationInterface
{
    public const string MIGRATION = '01a19896-b1fa-7ddc-82f9-edb5fe637be9';

    // the previous release recorded a save in both tables, a few milliseconds apart
    private const int SAME_SAVE_SECONDS = 2;

    // the entity types that had a legacy table
    private const array ENTITY_TYPES = array(EntityType::Experiments, EntityType::Items, EntityType::Templates, EntityType::ItemsTypes);

    private Db $Db;

    public function __construct(private OutputInterface $output)
    {
        $this->Db = Db::getConnection();
    }

    #[Override]
    public function up(): void
    {
        foreach (self::ENTITY_TYPES as $entityType) {
            $legacyTable = $entityType->value . '_revisions';
            if (!$this->tableExists($legacyTable)) {
                continue;
            }
            $Store = new BodyRevisionsStore($entityType);
            $imported = 0;
            $alreadyRecorded = 0;
            $entityIds = $this->readIds(sprintf(
                'SELECT DISTINCT r.item_id FROM %s AS r INNER JOIN %s AS e ON (e.id = r.item_id) ORDER BY r.item_id',
                $legacyTable,
                $entityType->value,
            ));
            foreach ($entityIds as $entityId) {
                [$entityImported, $entityAlreadyRecorded] = $this->transactional(fn(): array => $this->importEntity($Store, $entityType, $entityId));
                $imported += $entityImported;
                $alreadyRecorded += $entityAlreadyRecorded;
            }
            $orphans = (int) $this->Db->q(sprintf(
                'SELECT COUNT(*) FROM %s AS r LEFT JOIN %s AS e ON (e.id = r.item_id) WHERE e.id IS NULL',
                $legacyTable,
                $entityType->value,
            ))->fetchColumn();
            $this->output->writeln(sprintf(
                '%s: %d revisions imported, %d already recorded, %d of entries that do not exist anymore left out',
                $legacyTable,
                $imported,
                $alreadyRecorded,
                $orphans,
            ));
        }
    }

    #[Override]
    public function down(): void
    {
        foreach (self::ENTITY_TYPES as $entityType) {
            $legacyTable = $entityType->value . '_revisions';
            if (!$this->tableExists($legacyTable)) {
                throw new ImproperActionException(sprintf('Table %s does not exist: create it before copying the revisions back.', $legacyTable));
            }
            $Store = new BodyRevisionsStore($entityType);
            $exported = 0;
            $corrupted = array();
            $entityIds = $this->readIds(sprintf('SELECT DISTINCT entity_id FROM %s_body_revisions ORDER BY entity_id', $entityType->value));
            foreach ($entityIds as $entityId) {
                // not an arrow function: it would get a copy of $corrupted
                $exported += $this->transactional(function () use ($Store, $entityType, $entityId, &$corrupted): int {
                    return $this->exportEntity($Store, $entityType, $entityId, $corrupted);
                });
            }
            $this->output->writeln(sprintf('%s: %d revisions copied back', $legacyTable, $exported));
            if ($corrupted !== array()) {
                $this->output->writeln(sprintf('<error>%s: %d corrupted revisions could not be copied back: %s</error>', $legacyTable, count($corrupted), implode(', ', $corrupted)));
            }
        }
    }

    /**
     * Legacy revisions become a chain of their own, from the oldest
     *
     * @return array{int, int} imported, already recorded
     */
    private function importEntity(BodyRevisionsStore $Store, EntityType $entityType, int $entityId): array
    {
        $legacyTable = $entityType->value . '_revisions';
        // recorded by the previous release, or by a run of this migration that failed later on
        $recorded = array();
        $req = $this->Db->prepare(sprintf(
            'SELECT body_sha256, content_type, UNIX_TIMESTAMP(created_at) AS ts FROM %s_body_revisions WHERE entity_id = :entity_id',
            $entityType->value,
        ));
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        foreach ($req->fetchAll() as $revision) {
            $recorded[$revision['body_sha256'] . $revision['content_type']][] = (int) $revision['ts'];
        }

        // bodies are read one by one: an entry can have a lot of large revisions
        $req = $this->Db->prepare(sprintf(
            'SELECT id, content_type, created_at, UNIX_TIMESTAMP(created_at) AS ts, userid FROM %s WHERE item_id = :entity_id ORDER BY created_at ASC, id ASC',
            $legacyTable,
        ));
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $bodyReq = $this->Db->prepare(sprintf('SELECT body FROM %s WHERE id = :id', $legacyTable));

        $parent = null;
        $parentBody = '';
        $imported = 0;
        $alreadyRecorded = 0;
        foreach ($req->fetchAll() as $legacy) {
            $bodyReq->bindValue(':id', $legacy['id'], PDO::PARAM_INT);
            $this->Db->execute($bodyReq);
            $body = (string) $bodyReq->fetchColumn();
            $key = hash('sha256', $body) . $legacy['content_type'];
            if ($this->consume($recorded, $key, (int) $legacy['ts'])) {
                $alreadyRecorded++;
                continue;
            }
            $parent = $Store->insert($entityId, $body, (int) $legacy['content_type'], $parent, $parentBody, (int) $legacy['userid'], (string) $legacy['created_at']);
            $parentBody = $body;
            $imported++;
        }
        return array($imported, $alreadyRecorded);
    }

    /**
     * @param list<int> $corrupted ids of the revisions that cannot be rebuilt
     */
    private function exportEntity(BodyRevisionsStore $Store, EntityType $entityType, int $entityId, array &$corrupted): int
    {
        $legacyTable = $entityType->value . '_revisions';
        // each entry is copied in a transaction: if it has rows, a previous run copied it
        $req = $this->Db->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE item_id = :entity_id', $legacyTable));
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        if ((int) $req->fetchColumn() > 0) {
            return 0;
        }

        // the legacy tables require an author: the owner of the entry replaces a deleted user
        $req = $this->Db->prepare(sprintf(
            'INSERT INTO %1$s (item_id, body, content_type, created_at, modified_at, userid)
            VALUES (:item_id, :body, :content_type, :created_at, :modified_at, COALESCE(:userid, (SELECT userid FROM %2$s WHERE id = :entity_id)))',
            $legacyTable,
            $entityType->value,
        ));
        $exported = 0;
        foreach ($Store->iterate($entityId) as $revision) {
            // a corrupted revision must not stop the others from being copied
            if ($revision['body'] === null) {
                $corrupted[] = $revision['id'];
                continue;
            }
            $req->bindValue(':item_id', $entityId, PDO::PARAM_INT);
            $req->bindValue(':body', $revision['body']);
            $req->bindValue(':content_type', $revision['content_type'], PDO::PARAM_INT);
            $req->bindValue(':created_at', $revision['created_at']);
            $req->bindValue(':modified_at', $revision['created_at']);
            $this->Db->bindNullableInt($req, ':userid', $revision['users_id']);
            $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
            $this->Db->execute($req);
            $exported++;
        }
        return $exported;
    }

    /**
     * Remove a revision already recorded at about the same time from the list, if there is one
     *
     * @param array<string, array<int, int>> $recorded
     */
    private function consume(array &$recorded, string $key, int $timestamp): bool
    {
        foreach ($recorded[$key] ?? array() as $index => $recordedAt) {
            if (abs($recordedAt - $timestamp) <= self::SAME_SAVE_SECONDS) {
                unset($recorded[$key][$index]);
                return true;
            }
        }
        return false;
    }

    /**
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function transactional(callable $fn): mixed
    {
        $this->Db->beginTransaction();
        try {
            $result = $fn();
            $this->Db->commit();
            return $result;
        } catch (Throwable $e) {
            $this->Db->rollBack();
            throw $e;
        }
    }

    /**
     * @return list<int>
     */
    private function readIds(string $sql): array
    {
        $req = $this->Db->q($sql);
        $ids = array();
        foreach ($req->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $ids[] = (int) $id;
        }
        return $ids;
    }

    private function tableExists(string $table): bool
    {
        $req = $this->Db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
        $req->bindValue(':table', $table);
        $this->Db->execute($req);
        return (int) $req->fetchColumn() === 1;
    }
}

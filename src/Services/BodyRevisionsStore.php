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
use Elabftw\Exceptions\AppException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Generator;
use PDO;

use function count;
use function hash;
use function sprintf;
use function zstd_compress;
use function zstd_uncompress;

/**
 * Storage of the body revisions, without permissions checks: see the BodyRevisions model for that
 *
 * A revision is the body compressed with zstd, using the body of its parent revision as dictionary:
 * it costs about the size of the change, not the size of the body. Every KEYFRAME_INTERVAL revisions,
 * the body is compressed without dictionary (a keyframe), so rebuilding a body never decompresses more
 * than KEYFRAME_INTERVAL frames. Revisions point to their parent instead of relying on the id order:
 * two concurrent saves are two children of the same parent, and both stay readable.
 */
final class BodyRevisionsStore
{
    public const int KEYFRAME_INTERVAL = 50;

    // lower levels use a window too small to reach back into the dictionary when the body is several megabytes
    private const int COMPRESSION_LEVEL = 9;

    private Db $Db;

    private string $table;

    public function __construct(private EntityType $entityType)
    {
        $this->Db = Db::getConnection();
        $this->table = $entityType->value . '_body_revisions';
    }

    /**
     * The revision recorded last: the current body of the entity is expected to be this one
     *
     * @return array{id: int, depth: int, body_sha256: string, content_type: int}|null
     */
    public function readLatest(int $entityId): ?array
    {
        $sql = sprintf('SELECT id, depth, body_sha256, content_type FROM %s WHERE entity_id = :entity_id ORDER BY id DESC LIMIT 1', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        /** @var array{id: int, depth: int, body_sha256: string, content_type: int}|false $latest */
        $latest = $req->fetch();
        return $latest === false ? null : $latest;
    }

    /**
     * @param array{id: int, depth: int, body_sha256: string, content_type: int}|null $parent
     * @param string $parentBody body of the parent revision, used as dictionary
     * @param ?string $createdAt null for now
     * @return array{id: int, depth: int, body_sha256: string, content_type: int}
     */
    public function insert(int $entityId, string $body, int $contentType, ?array $parent, string $parentBody, ?int $userid, ?string $createdAt = null): array
    {
        // a keyframe is compressed on its own: no parent, nothing to use as dictionary, or the chain is long enough
        $parentId = null;
        $depth = 0;
        if ($parent !== null && $parentBody !== '' && $parent['depth'] + 1 < self::KEYFRAME_INTERVAL) {
            $parentId = $parent['id'];
            $depth = $parent['depth'] + 1;
        }
        $revision = array(
            'id' => 0,
            'depth' => $depth,
            'body_sha256' => hash('sha256', $body),
            'content_type' => $contentType,
        );
        // the user might not exist anymore when this is the last person who changed the entity
        $sql = sprintf(
            'INSERT INTO %s (entity_id, parent_id, depth, body_zstd, body_sha256, content_type, users_id, created_at)
            VALUES (:entity_id, :parent_id, :depth, :body_zstd, :body_sha256, :content_type,
                (SELECT userid FROM users WHERE userid = :users_id), COALESCE(:created_at, CURRENT_TIMESTAMP))',
            $this->table,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':parent_id', $parentId);
        $req->bindValue(':depth', $depth, PDO::PARAM_INT);
        // LOB makes PDO send it as binary instead of a string in the connection charset
        $req->bindValue(':body_zstd', $this->compress($body, $parentId === null ? null : $parentBody), PDO::PARAM_LOB);
        $req->bindValue(':body_sha256', $revision['body_sha256']);
        $req->bindValue(':content_type', $contentType, PDO::PARAM_INT);
        $this->Db->bindNullableInt($req, ':users_id', $userid);
        $req->bindValue(':created_at', $createdAt);
        $this->Db->execute($req);
        $revision['id'] = $this->Db->lastInsertId();
        return $revision;
    }

    /**
     * Rebuild the body of a revision from the keyframe of its chain
     */
    public function readBody(int $entityId, int $id): string
    {
        // parents always have a lower id, so this is the chain from its keyframe to the requested revision
        $sql = sprintf(
            'WITH RECURSIVE chain AS (
                SELECT id, parent_id, body_sha256, body_zstd FROM %1$s WHERE id = :id AND entity_id = :entity_id
                UNION ALL
                SELECT r.id, r.parent_id, r.body_sha256, r.body_zstd FROM %1$s AS r INNER JOIN chain ON (r.id = chain.parent_id)
            )
            SELECT parent_id, body_sha256, body_zstd FROM chain ORDER BY id ASC',
            $this->table,
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':id', $id, PDO::PARAM_INT);
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $chain = $req->fetchAll();
        if ($chain === array()) {
            throw new ResourceNotFoundException();
        }
        $body = '';
        foreach ($chain as $frame) {
            $body = $this->uncompress($frame['body_zstd'], $frame['parent_id'] === null ? null : $body);
        }
        // a missing or altered frame anywhere in the chain ends up here
        $this->verify($body, $chain[count($chain) - 1]['body_sha256'], $id);
        return $body;
    }

    /**
     * Every revision of an entity with its body, in the order they were recorded
     * The body is null when it cannot be rebuilt: the revision, or one of its parents, is corrupted.
     *
     * @return Generator<array{id: int, content_type: int, created_at: string, users_id: ?int, body: ?string}>
     */
    public function iterate(int $entityId): Generator
    {
        $sql = sprintf('SELECT id, parent_id, body_sha256, body_zstd, content_type, created_at, users_id FROM %s WHERE entity_id = :entity_id ORDER BY id ASC', $this->table);
        $req = $this->Db->prepare($sql);
        $req->bindValue(':entity_id', $entityId, PDO::PARAM_INT);
        $this->Db->execute($req);
        $frames = $req->fetchAll();
        // a body is kept only until its last child is rebuilt
        $childrenLeft = array();
        foreach ($frames as $frame) {
            if ($frame['parent_id'] !== null) {
                $childrenLeft[$frame['parent_id']] = ($childrenLeft[$frame['parent_id']] ?? 0) + 1;
            }
        }
        $bodies = array();
        foreach ($frames as $frame) {
            $parentId = $frame['parent_id'];
            $body = null;
            if ($parentId === null || isset($bodies[$parentId])) {
                try {
                    $body = $this->uncompress($frame['body_zstd'], $parentId === null ? null : $bodies[$parentId]);
                    $this->verify($body, $frame['body_sha256'], $frame['id']);
                } catch (AppException) {
                    $body = null;
                }
            }
            if ($parentId !== null && --$childrenLeft[$parentId] === 0) {
                unset($bodies[$parentId]);
            }
            if ($body !== null && isset($childrenLeft[$frame['id']])) {
                $bodies[$frame['id']] = $body;
            }
            yield array(
                'id' => $frame['id'],
                'content_type' => $frame['content_type'],
                'created_at' => $frame['created_at'],
                'users_id' => $frame['users_id'],
                'body' => $body,
            );
        }
    }

    // a body is valid UTF-8 so it can never start with the magic number of a zstd dictionary: it is always used as raw content
    private function compress(string $body, ?string $dictionary): string
    {
        $frame = zstd_compress($body, self::COMPRESSION_LEVEL, $dictionary);
        if ($frame === false) {
            throw new AppException(sprintf('Could not compress a body revision of %s.', $this->entityType->value));
        }
        return $frame;
    }

    private function uncompress(string $frame, ?string $dictionary): string
    {
        $body = zstd_uncompress($frame, $dictionary);
        if ($body === false) {
            throw new AppException(sprintf('Could not decompress a body revision of %s.', $this->entityType->value));
        }
        return $body;
    }

    private function verify(string $body, string $sha256, int $id): void
    {
        if (hash('sha256', $body) !== $sha256) {
            throw new AppException(sprintf('Body revision %d of %s is corrupted.', $id, $this->entityType->value));
        }
    }
}

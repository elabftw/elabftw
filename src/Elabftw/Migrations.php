<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use League\Flysystem\FilesystemOperator;
use PDO;
use Symfony\Component\Uid\Uuid;

use function array_column;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function count;
use function dirname;
use function ksort;
use function preg_match;
use function sprintf;
use function substr;

/**
 * Discover UUIDv7 migration files and keep track of applied migrations.
 */
final class Migrations
{
    public const string FILE_REGEX = '/^migration-([0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})(?:_[a-z0-9][a-z0-9-]*)?\.sql$/';

    private ?Db $Db;

    public function __construct(private readonly FilesystemOperator $fs, ?Db $Db = null)
    {
        $this->Db = $Db;
    }

    public static function getDefault(): self
    {
        return new self(FsTools::getFs(dirname(__DIR__) . '/sql'));
    }

    public static function generateUuidV7(): string
    {
        return Uuid::v7()->toRfc4122();
    }

    public static function isUuidV7(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) === 1;
    }

    /**
     * @return array<string, string> UUID => filename, sorted chronologically by UUIDv7.
     */
    public function getAvailable(): array
    {
        $available = array();
        foreach ($this->fs->listContents('', false) as $entry) {
            if (!$entry->isFile()) {
                continue;
            }
            $filename = $entry->path();
            if (preg_match(self::FILE_REGEX, $filename, $matches) === 1) {
                $available[$matches[1]] = $filename;
            }
        }
        ksort($available, SORT_STRING);
        return $available;
    }

    /**
     * @return array<string, string> UUID => filename.
     */
    public function getPending(): array
    {
        $available = $this->getAvailable();
        if ($available === array()) {
            return array();
        }
        return array_diff_key($available, array_flip($this->getApplied()));
    }

    /**
     * @return list<string>
     */
    public function getApplied(): array
    {
        if (!$this->tableExists()) {
            return array();
        }
        $req = $this->db()->q('SELECT migration FROM schema_migrations ORDER BY migration ASC');
        return array_column($req->fetchAll(), 'migration');
    }

    public function getPendingCount(): int
    {
        return count($this->getPending());
    }

    /**
     * Fresh installs use structure.sql, which already contains the latest structure.
     * Record all migration files so they are not executed on first boot.
     */
    public function markAllAvailableAsApplied(): void
    {
        $available = array_keys($this->getAvailable());
        if ($available === array()) {
            return;
        }
        $batch = $this->nextBatch();
        foreach ($available as $migration) {
            if (!$this->isApplied($migration)) {
                $this->recordApplied($migration, $batch);
            }
        }
    }

    public function ensureTable(): void
    {
        $this->db()->q(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                migration CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                batch INT UNSIGNED NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (migration),
                KEY idx_schema_migrations_batch (batch)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
        );
    }

    public function nextBatch(): int
    {
        $this->ensureTable();
        $req = $this->db()->q('SELECT COALESCE(MAX(batch), 0) + 1 AS next_batch FROM schema_migrations');
        return (int) $req->fetchColumn();
    }

    public function recordApplied(string $migration, int $batch): void
    {
        $this->ensureTable();
        $req = $this->db()->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (:migration, :batch)');
        $req->bindValue(':migration', $migration);
        $req->bindValue(':batch', $batch, PDO::PARAM_INT);
        $this->db()->execute($req);
    }

    public function removeApplied(string $migration): void
    {
        if (!$this->tableExists()) {
            return;
        }
        $req = $this->db()->prepare('DELETE FROM schema_migrations WHERE migration = :migration');
        $req->bindValue(':migration', $migration);
        $this->db()->execute($req);
    }

    public function isApplied(string $migration): bool
    {
        if (!$this->tableExists()) {
            return false;
        }
        $req = $this->db()->prepare('SELECT COUNT(*) FROM schema_migrations WHERE migration = :migration');
        $req->bindValue(':migration', $migration);
        $this->db()->execute($req);
        return (int) $req->fetchColumn() === 1;
    }

    public function getFilename(string $migration): ?string
    {
        return $this->getAvailable()[$migration] ?? null;
    }

    public static function getDownFilename(string $filename): string
    {
        return substr($filename, 0, -4) . '.down.sql';
    }

    /**
     * Return the UUIDs from the most recently applied batch, newest first.
     *
     * @return list<string>
     */
    public function getLastBatch(): array
    {
        if (!$this->tableExists()) {
            return array();
        }
        $req = $this->db()->q(
            'SELECT migration FROM schema_migrations
             WHERE batch = (SELECT MAX(batch) FROM schema_migrations)
             ORDER BY migration DESC'
        );
        return array_column($req->fetchAll(), 'migration');
    }

    private function tableExists(): bool
    {
        $req = $this->db()->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = 'schema_migrations'"
        );
        $this->db()->execute($req);
        return (int) $req->fetchColumn() === 1;
    }

    private function db(): Db
    {
        $this->Db ??= Db::getConnection();
        return $this->Db;
    }
}

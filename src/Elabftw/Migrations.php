<?php

/**
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use League\Flysystem\FilesystemOperator;
use PDO;
use Symfony\Component\Console\Output\OutputInterface;

use function array_diff;
use function array_keys;
use function array_slice;
use function ctype_digit;
use function preg_match;
use function sort;
use function sprintf;
use function array_values;
use function array_reverse;
use function array_filter;
use function count;
use function implode;

/**
 * Timestamped SQL migrations, tracked individually in execution order.
 * The numbered schemas up to REQUIRED_SCHEMA remain the legacy baseline.
 */
final class Migrations
{
    private Db $Db;

    private Sql $Sql;

    public function __construct(private FilesystemOperator $fs, OutputInterface $output)
    {
        $this->Db = Db::getConnection();
        $this->Sql = new Sql($fs, $output);
    }

    /** @return list<string> */
    public function available(): array
    {
        $names = array();
        foreach ($this->fs->listContents('migrations') as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (preg_match('/^migrations\/(\d{4}_\d{2}_\d{2}_\d{6}_[a-z][a-z0-9_]*)\.sql$/D', $file->path(), $matches)) {
                $names[] = $matches[1];
            }
        }
        sort($names);
        return $names;
    }

    public function isInstalled(): bool
    {
        return (bool) $this->Db->q("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'schema_migrations'")->fetchColumn();
    }

    /** @return array<string, int> Migration IDs and batches, in execution order. */
    public function applied(): array
    {
        if (!$this->isInstalled()) {
            return array();
        }
        $applied = array();
        foreach ($this->Db->q('SELECT migration, batch FROM schema_migrations ORDER BY id')->fetchAll() as $row) {
            $applied[$row['migration']] = (int) $row['batch'];
        }
        return $applied;
    }

    /** @return list<string> */
    public function pending(): array
    {
        return array_values(array_diff($this->available(), array_keys($this->applied())));
    }

    public function isCurrent(): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }
        $available = $this->available();
        $applied = array_keys($this->applied());
        return array_diff($available, $applied) === array()
            && array_diff($applied, $available) === array();
    }

    public function migrate(bool $step = false): int
    {
        return $this->withLock(function () use ($step): int {
            $this->assertBaseline();
            $this->Sql->execFile('migrations.sql');
            $this->assertKnownMigrations();
            $pending = $this->pending();
            // Fail before changing the database if any rollback file is missing.
            foreach ($pending as $name) {
                $this->assertFiles($name);
            }
            $batch = $this->nextBatch();
            foreach ($pending as $name) {
                $this->Sql->execFileWithRollback('migrations/' . $name . '.sql', 'migrations/' . $name . '-down.sql');
                $this->record($name, $batch);
                if ($step) {
                    $batch++;
                }
            }
            return count($pending);
        });
    }

    /** Roll back the last batch, the last N migrations, or the latest migration by ID. */
    public function rollback(?string $name = null, int $steps = 0): int
    {
        return $this->withLock(function () use ($name, $steps): int {
            $this->assertBaseline();
            $applied = $this->applied();
            $names = array_reverse(array_keys($applied));
            if ($name !== null) {
                if (($names[0] ?? null) !== $name) {
                    throw new ImproperActionException('Only the most recently applied migration can be reverted by ID.');
                }
                $names = array($name);
            } elseif ($steps > 0) {
                $names = array_slice($names, 0, $steps);
            } elseif ($names !== array()) {
                $batch = $applied[$names[0]];
                $names = array_values(array_filter($names, fn(string $id): bool => $applied[$id] === $batch));
            }
            // Validate the entire plan before executing any down SQL.
            foreach ($names as $id) {
                $this->assertFiles($id);
            }
            foreach ($names as $id) {
                $this->Sql->execFile('migrations/' . $id . '-down.sql');
                $this->forget($id);
            }
            return count($names);
        });
    }

    /** Explicit history repair: does not execute migration SQL. */
    public function mark(string $name, bool $forget = false): int
    {
        return $this->withLock(function () use ($name, $forget): int {
            $this->assertBaseline();
            $this->assertFiles($name);
            $this->Sql->execFile('migrations.sql');
            if ($forget) {
                $this->forget($name);
            } elseif (!isset($this->applied()[$name])) {
                $this->record($name, $this->nextBatch());
            }
            return 1;
        });
    }

    /** Keep recovery of the old numbered schemas available without corrupting new history. */
    public function revertLegacy(string $number, bool $force = false): int
    {
        return $this->withLock(function () use ($number, $force): int {
            if (!ctype_digit($number) || (int) $number < 1 || (int) $number > SchemaVersionChecker::REQUIRED_SCHEMA) {
                throw new ImproperActionException('Invalid legacy schema number.');
            }
            if ($this->applied() !== array()) {
                throw new ImproperActionException('Revert timestamped migrations before reverting a legacy schema.');
            }
            $current = (int) $this->Db->q("SELECT conf_value FROM config WHERE conf_name = 'schema'")->fetchColumn();
            if ($current !== (int) $number) {
                throw new ImproperActionException('Only the current legacy schema can be reverted.');
            }
            return $this->Sql->execFile(sprintf('schema%d-down.sql', (int) $number), $force);
        });
    }

    public function forceLegacy(string $number): int
    {
        return $this->withLock(function () use ($number): int {
            if (!ctype_digit($number) || (int) $number < 1 || (int) $number > SchemaVersionChecker::REQUIRED_SCHEMA) {
                throw new ImproperActionException('Invalid legacy schema number.');
            }
            if ($this->applied() !== array()) {
                throw new ImproperActionException('Cannot change the legacy schema while timestamped migrations are recorded.');
            }
            $req = $this->Db->prepare("UPDATE config SET conf_value = :schema WHERE conf_name = 'schema'");
            $req->bindValue(':schema', $number);
            $this->Db->execute($req);
            return 1;
        });
    }

    private function assertBaseline(): void
    {
        $current = (int) $this->Db->q("SELECT conf_value FROM config WHERE conf_name = 'schema'")->fetchColumn();
        if ($current !== SchemaVersionChecker::REQUIRED_SCHEMA) {
            throw new ImproperActionException('Run db:update to reach the legacy baseline before using timestamped migrations.');
        }
    }

    private function assertKnownMigrations(): void
    {
        $unknown = array_diff(array_keys($this->applied()), $this->available());
        if ($unknown !== array()) {
            throw new ImproperActionException('Applied migrations are missing from this checkout: ' . implode(', ', $unknown));
        }
    }

    private function assertFiles(string $name): void
    {
        if (!preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_[a-z][a-z0-9_]{0,172}$/D', $name)
            || !$this->fs->fileExists('migrations/' . $name . '.sql')
            || !$this->fs->fileExists('migrations/' . $name . '-down.sql')) {
            throw new ImproperActionException('Missing or invalid migration SQL pair: ' . $name);
        }
    }

    private function nextBatch(): int
    {
        return (int) $this->Db->q('SELECT COALESCE(MAX(batch), 0) + 1 FROM schema_migrations')->fetchColumn();
    }

    private function record(string $name, int $batch): void
    {
        $req = $this->Db->prepare('INSERT INTO schema_migrations (migration, batch) VALUES (:migration, :batch)');
        $req->bindValue(':migration', $name);
        $req->bindValue(':batch', $batch, PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    private function forget(string $name): void
    {
        $req = $this->Db->prepare('DELETE FROM schema_migrations WHERE migration = :migration');
        $req->bindValue(':migration', $name);
        $this->Db->execute($req);
    }

    /** Serialize migration/history writes for this database, including concurrent CLI runs. */
    private function withLock(callable $operation): int
    {
        $lock = "CONCAT('elabftw:migrations:', LEFT(SHA2(DATABASE(), 256), 40))";
        if ((int) $this->Db->q('SELECT GET_LOCK(' . $lock . ', 0)')->fetchColumn() !== 1) {
            throw new ImproperActionException('Another migration command is already running.');
        }
        try {
            return $operation();
        } finally {
            $this->Db->q('SELECT RELEASE_LOCK(' . $lock . ')');
        }
    }
}

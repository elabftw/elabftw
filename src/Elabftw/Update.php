<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use PDO;

use function bin2hex;
use function random_bytes;
use function sha1;
use function sprintf;
use function preg_match;

/**
 * Run the update schema script
 *
 * Numeric schemas are kept as a legacy upgrade path up to REQUIRED_SCHEMA.
 * New migrations use UUIDv7 filenames and are tracked independently in schema_migrations.
 *
 * How to modify the structure:
 * 1. Generate a migration with `bin/console dev:genschema descriptive_name`.
 * 2. Edit the generated .sql and .down.sql files.
 * 3. Run `bin/console db:update` to test the migration.
 * 4. Reflect the final structure in src/sql/structure.sql (or Models/Config.php for config entries).
 */
final class Update
{
    private Db $Db;

    public function __construct(
        private int $currentSchema,
        private readonly Sql $Sql,
        private readonly Migrations $Migrations,
    ) {
        $this->Db = Db::getConnection();
    }

    /**
     * Update the database schema if needed
     */
    public function runUpdateScript(bool $force = false): int
    {
        // make sure we run MySQL version 8.0 at least
        $mysqlVersion = (string) $this->Db->getAttribute(PDO::ATTR_SERVER_VERSION);
        if (preg_match('/^(\d+)\.(\d+)/', $mysqlVersion, $matches) !== 1
            || (int) $matches[1] < 8
        ) {
            throw new ImproperActionException(sprintf('MySQL 8.0 is required, found %s', $mysqlVersion));
        }

        // old style update functions have been removed, so add a block to prevent upgrade from very very old to newest directly
        if ($this->currentSchema < 37) {
            throw new ImproperActionException('Please update first to latest version from 1.8 branch before updating to 2.0 branch! See documentation.');
        }

        if ($this->currentSchema < 41) {
            throw new ImproperActionException('Please update first to latest version from 2.0 branch before updating to 3.0 branch! See documentation.');
        }

        // Keep the historical numeric migrations as the upgrade path for existing installations.
        $Config = Config::getConfig();
        while ($this->currentSchema < SchemaVersionChecker::REQUIRED_SCHEMA) {
            $nextSchema = $this->currentSchema + 1;
            $this->Sql->execFileWithRollback(
                sprintf('schema%d.sql', $nextSchema),
                sprintf('schema%d-down.sql', $nextSchema),
                $force,
            );
            $this->currentSchema = $nextSchema;
            // this will bust cache
            $Config->patch(Action::Update, array('schema' => $this->currentSchema));
            // schema57: add an elabid to existing database items
            if ($this->currentSchema === 57) {
                $this->addElabidToItems();
                $this->fixExperimentsRevisions();
            }
        }

        $this->runUuidMigrations($force);
        return $this->currentSchema;
    }

    private function runUuidMigrations(bool $force): void
    {
        $pending = $this->Migrations->getPending();
        if ($pending === array()) {
            return;
        }

        $batch = $this->Migrations->nextBatch();
        foreach ($pending as $migration => $filename) {
            $this->Sql->execFileWithRollback(
                $filename,
                Migrations::getDownFilename($filename),
                $force,
            );
            $this->Migrations->recordApplied($migration, $batch);
        }
    }

    private function addElabidToItems(): void
    {
        $sql = 'SELECT id, date FROM items';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $items = $req->fetchAll();
        if (empty($items)) {
            return;
        }

        $sql = 'UPDATE items SET elabid = :elabid WHERE id = :id';
        $req = $this->Db->prepare($sql);
        foreach ($items as $item) {
            $elabid = $item['date'] . '-' . sha1(bin2hex(random_bytes(16)));
            $req->bindParam(':id', $item['id'], PDO::PARAM_INT);
            $req->bindParam(':elabid', $elabid);
            $this->Db->execute($req);
        }
    }

    /**
     * Remove revision without corresponding experiment and add
     * missing constraints when users employed the structure.sql
     */
    private function fixExperimentsRevisions(): void
    {
        $sql = 'SELECT * FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME = :name1 OR CONSTRAINT_NAME= :name2';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name1', 'fk_experiments_revisions_experiments_id');
        $req->bindValue(':name2', 'fk_experiments_revisions_users_userid');
        $this->Db->execute($req);

        if ($req->rowCount() === 0) {
            $sql = 'ALTER TABLE `experiments_revisions`
                ADD CONSTRAINT `fk_experiments_revisions_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_experiments_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;';
            $this->Db->q($sql);
        }
    }
}

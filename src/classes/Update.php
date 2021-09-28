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

use function bin2hex;
use function dirname;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use Elabftw\Models\Config;
use FilesystemIterator;
use PDO;
use function random_bytes;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function sha1;

/**
 * Use this to check for latest version or update the database schema
 *
 * How to modify the structure:
 * 1. add a file in src/sql/ named 'schemaXX.sql' where XX is the current schema version + 1
 * 2. this file should use transactions (see other files for examples)
 * 3. increment the REQUIRED_SCHEMA number
 * 4. Run `bin/console db:update`
 * 5. reflect the changes in src/sql/structure.sql (or models/Config.php for the config table)
 */
class Update
{
    /** @var int REQUIRED_SCHEMA the current version of the database structure */
    private const REQUIRED_SCHEMA = 65;

    private Db $Db;

    public function __construct(private int $currentSchema, private Sql $Sql)
    {
        $this->Db = Db::getConnection();
    }

    /**
     * Get the current required schema
     */
    public static function getRequiredSchema(): int
    {
        return self::REQUIRED_SCHEMA;
    }

    /**
     * Check if the Db structure needs updating
     */
    public function checkSchema(): void
    {
        if ($this->currentSchema !== self::REQUIRED_SCHEMA) {
            throw new InvalidSchemaException();
        }
    }

    /**
     * Update the database schema if needed
     */
    public function runUpdateScript(): void
    {
        // do nothing if we're up to date
        if ($this->currentSchema === self::REQUIRED_SCHEMA) {
            return;
        }

        // old style update functions have been removed, so add a block to prevent upgrade from very very old to newest directly
        if ($this->currentSchema < 37) {
            throw new ImproperActionException('Please update first to latest version from 1.8 branch before updating to 2.0 branch! See documentation.');
        }

        if ($this->currentSchema < 41) {
            throw new ImproperActionException('Please update first to latest version from 2.0 branch before updating to 3.0 branch! See documentation.');
        }

        // new style with SQL files instead of functions
        while ($this->currentSchema < self::REQUIRED_SCHEMA) {
            ++$this->currentSchema;
            $this->Sql->execFile('schema' . (string) ($this->currentSchema) . '.sql');
            // schema57: add an elabid to existing database items
            if ($this->currentSchema === 57) {
                $this->addElabidToItems();
                $this->fixExperimentsRevisions();
            }
        }


        // remove cached twig templates (for non docker users)
        $this->cleanTmp();
    }

    /**
     * Delete things in the tmp folder (cache/elab)
     */
    private function cleanTmp(): void
    {
        $dir = dirname(__DIR__, 2) . '/cache/elab';
        if (!is_dir($dir)) {
            return;
        }
        $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file->getPathName()) : unlink($file->getPathName());
        }
    }

    private function addElabidToItems(): void
    {
        $sql = 'SELECT id, date FROM items';
        $req = $this->Db->prepare($sql);
        $req->execute();
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
            $req->execute();
        }
    }

    /**
     * Remove revision without corresponding experiment and add
     * missing constraints when users employed the structure.sql
     */
    private function fixExperimentsRevisions(): void
    {
        // delete all experiments_revisions where userid doesn't exist anymore
        // we do this to prevent having an integrity constraint violation when adding the constraint later
        $sql = 'DELETE FROM experiments_revisions WHERE userid NOT IN (SELECT users.userid FROM users)';
        $req = $this->Db->prepare($sql);
        $req->execute();
        // do the same for experiments
        $sql = 'DELETE FROM experiments_revisions WHERE item_id NOT IN (SELECT experiments.id FROM experiments)';
        $req = $this->Db->prepare($sql);
        $req->execute();

        $sql = 'SELECT * FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_NAME = :name1 OR CONSTRAINT_NAME= :name2';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':name1', 'fk_experiments_revisions_experiments_id');
        $req->bindValue(':name2', 'fk_experiments_revisions_users_userid');
        $req->execute();

        if ($req->rowCount() === 0) {
            // Now, add the constraints
            $sql = 'ALTER TABLE `experiments_revisions`
                ADD CONSTRAINT `fk_experiments_revisions_experiments_id` FOREIGN KEY (`item_id`) REFERENCES `experiments`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                ADD CONSTRAINT `fk_experiments_revisions_users_userid` FOREIGN KEY (`userid`) REFERENCES `users`(`userid`) ON DELETE CASCADE ON UPDATE CASCADE;';
            $this->Db->q($sql);
        }
    }
}

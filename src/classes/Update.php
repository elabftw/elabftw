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
use Elabftw\Exceptions\InvalidSchemaException;
use Elabftw\Models\Config;
use PDO;

use function bin2hex;
use function random_bytes;
use function sha1;
use function sprintf;

/**
 * Use this to check for latest version or update the database schema
 *
 * How to modify the structure:
 * 1. Generate a schema with bin/console dev:genschema
 * 2. Fix permissions as they might be owned by root from the container
 * 3. Edit them to make changes in the db in both directions (up and down)
 * 4. Run `bin/console db:update` to apply the changes as if you were upgrading
 * 5. reflect the changes in src/sql/structure.sql (or models/Config.php for the config table)
 */
final class Update
{
    /** @var int REQUIRED_SCHEMA the current version of the database structure */
    public const int REQUIRED_SCHEMA = 177;

    private Db $Db;

    public function __construct(private int $currentSchema, private Sql $Sql)
    {
        $this->Db = Db::getConnection();
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
    public function runUpdateScript(bool $force = false): array
    {
        // at the end of the update, warnings can be displayed for important information
        $warn = array();

        // make sure we run MySQL version 8 at least
        $mysqlVersion = (int) substr($this->Db->getAttribute(PDO::ATTR_SERVER_VERSION) ?? '1', 0, 1);
        if ($mysqlVersion < 8) {
            throw new ImproperActionException('It looks like MySQL server version is less than 8. Update your MySQL server!');
        }

        // old style update functions have been removed, so add a block to prevent upgrade from very very old to newest directly
        if ($this->currentSchema < 37) {
            throw new ImproperActionException('Please update first to latest version from 1.8 branch before updating to 2.0 branch! See documentation.');
        }

        if ($this->currentSchema < 41) {
            throw new ImproperActionException('Please update first to latest version from 2.0 branch before updating to 3.0 branch! See documentation.');
        }

        // new style with SQL files instead of functions
        $Config = Config::getConfig();
        while ($this->currentSchema < self::REQUIRED_SCHEMA) {
            ++$this->currentSchema;
            $this->Sql->execFile(sprintf('schema%d.sql', $this->currentSchema), $force);
            // this will bust cache
            $Config->patch(Action::Update, array('schema' => $this->currentSchema));
            // schema57: add an elabid to existing database items
            if ($this->currentSchema === 57) {
                $this->addElabidToItems();
                $this->fixExperimentsRevisions();
            }
        }

        return $warn;
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

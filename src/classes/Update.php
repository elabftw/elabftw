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

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidSchemaException;
use Elabftw\Models\Config;
use FilesystemIterator;
use PDO;

/**
 * Use this to check for latest version or update the database schema
 *
 * How to modify the structure:
 * 1. add a file in src/sql/ named 'schemaXX.sql' where XX is the current schema version + 1
 * 2. this file should use transactions (see other files for examples)
 * 3. increment the REQUIRED_SCHEMA number
 * 4. Run `bin/console db:update`
 * 5. reflect the changes in src/sql/structure.sql
 * 6. reflect the changes in tests/_data/phpunit.sql if needed
 */
class Update
{
    /** @var int REQUIRED_SCHEMA the current version of the database structure */
    private const REQUIRED_SCHEMA = 56;

    /** @var Config $Config instance of Config */
    public $Config;

    /** @var Db $Db SQL Database */
    private $Db;

    /** @var Sql $Sql instance of Sql */
    private $Sql;

    /**
     * Constructor
     *
     * @param Config $config
     * @param Sql $sql
     */
    public function __construct(Config $config, Sql $sql)
    {
        $this->Config = $config;
        $this->Db = Db::getConnection();
        $this->Sql = $sql;
    }

    /**
     * Get the current required schema
     *
     * @return int required schema number
     */
    public function getRequiredSchema(): int
    {
        return self::REQUIRED_SCHEMA;
    }

    /**
     * Check if the Db structure needs updating
     *
     * @return void
     */
    public function checkSchema(): void
    {
        $currentSchema = (int) $this->Config->configArr['schema'];

        if ($currentSchema !== self::REQUIRED_SCHEMA) {
            throw new InvalidSchemaException();
        }
    }

    /**
     * Update the database schema if needed
     *
     * @return void
     */
    public function runUpdateScript(): void
    {
        $currentSchema = (int) $this->Config->configArr['schema'];

        // do nothing if we're up to date
        if ($currentSchema === self::REQUIRED_SCHEMA) {
            return;
        }

        // this is the old deprecated way of doing things
        if ($currentSchema < 37) {
            throw new ImproperActionException('Please update first to latest version from 1.8 branch before updating to 2.0 branch! See documentation.');
        }

        if ($currentSchema < 38) {
            // 20180402 v2.0.0
            $this->schema38();
            $this->updateSchema(38);
        }
        if ($currentSchema < 39) {
            // 20180406 v2.0.0
            $this->schema39();
            $this->updateSchema(39);
        }
        if ($currentSchema < 40) {
            // 20180513 v2.0.0
            $this->schema40();
            $this->updateSchema(40);
        }
        if ($currentSchema < 41) {
            // 20180602 v2.0.0
            $this->schema41();
            $this->updateSchema(41);
        }
        // end old style update

        // new style with SQL files instead of functions
        while ($currentSchema < self::REQUIRED_SCHEMA) {
            $this->Sql->execFile('schema' . (string) (++$currentSchema) . '.sql');
        }

        // remove cached twig templates (for non docker users)
        $this->cleanTmp();
    }

    /**
     * Delete things in the tmp folder (cache/elab)
     *
     * @return void
     */
    private function cleanTmp(): void
    {
        $dir = \dirname(__DIR__, 2) . '/cache/elab';
        if (!is_dir($dir)) {
            return;
        }
        $di = new \RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file->getPathName()) : unlink($file->getPathName());
        }
    }

    /**
     * Update the schema value in config to latest because we did the update functions before
     *
     * @param int $schema the version we want to update
     * @return void
     */
    private function updateSchema(int $schema): void
    {
        $config_arr = array('schema' => $schema);
        $this->Config->update($config_arr);
    }

    /**
     * Add items_comments and rename exp_id to item_id in experiments_comments
     *
     * @return void
     */
    private function schema38(): void
    {
        $sql = 'ALTER TABLE experiments_comments CHANGE exp_id item_id INT(10) UNSIGNED NOT NULL';
        $this->Db->q($sql);
        $sql = 'CREATE TABLE IF NOT EXISTS `items_comments` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `datetime` datetime NOT NULL,
          `item_id` int(11) NOT NULL,
          `comment` text NOT NULL,
          `userid` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        );';
        $this->Db->q($sql);
    }

    /**
     * Remove can_lock from users table
     *
     * @return void
     */
    private function schema39(): void
    {
        $sql = 'ALTER TABLE `users` DROP `can_lock`';
        $this->Db->q($sql);
    }

    /**
     * Add allow_edit to Users
     *
     * @return void
     */
    private function schema40(): void
    {
        $sql = "ALTER TABLE `users` ADD `allow_edit` TINYINT(1) NOT NULL DEFAULT '0'";
        $this->Db->q($sql);
    }

    /**
     * Merge the experiments_tags, items_tags and experiments_tpl_tags tables into tags and tags2entity tables.
     *
     * @return void
     */
    private function schema41(): void
    {
        // first create the tags table
        $sql = 'CREATE TABLE IF NOT EXISTS `tags` ( `id` INT NOT NULL AUTO_INCREMENT , `team` INT NOT NULL , `tag` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`))';

        $this->Db->q($sql);
        // now create the mapping table
        $sql = 'CREATE TABLE IF NOT EXISTS `tags2entity` ( `item_id` INT NOT NULL , `tag_id` INT NOT NULL , `item_type` VARCHAR(255) NOT NULL)';
        $this->Db->q($sql);

        // fetch existing tags
        $sql = 'SELECT experiments_tags.*, users.team FROM experiments_tags INNER JOIN users ON (experiments_tags.userid = users.userid)';
        $req = $this->Db->prepare($sql);
        $req->execute();
        $experimentsTags = $req->fetchAll();
        if ($experimentsTags === false) {
            $experimentsTags = array();
        }

        // same for items tags
        $sql = 'SELECT * FROM items_tags';
        $req = $this->Db->prepare($sql);
        $req->execute();
        $itemsTags = $req->fetchAll();
        if ($itemsTags === false) {
            $itemsTags = array();
        }

        // same for experiments_tpl_tags
        $sql = 'SELECT experiments_tpl_tags.*, users.team FROM experiments_tpl_tags INNER JOIN users ON (experiments_tpl_tags.userid = users.userid)';
        $req = $this->Db->prepare($sql);
        $req->execute();
        $tplTags = $req->fetchAll();
        if ($tplTags === false) {
            $tplTags = array();
        }

        // now the insert part
        $insertSql = 'INSERT INTO tags (team, tag) VALUES (:team, :tag)';
        $insertReq = $this->Db->prepare($insertSql);

        $insertSql2 = 'INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)';
        $insertReq2 = $this->Db->prepare($insertSql2);

        foreach ($experimentsTags as $tag) {
            // check if the tag doesn't exist already for the team
            $sql = 'SELECT id FROM tags WHERE tag = :tag AND team = :team';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':tag', $tag['tag']);
            $req->bindParam(':team', $tag['team'], PDO::PARAM_INT);
            $req->execute();
            $res = $req->fetchColumn();
            if ($req->rowCount() === 0) {
                // tag doesn't exist already
                $insertReq->bindParam(':team', $tag['team'], PDO::PARAM_INT);
                $insertReq->bindParam(':tag', $tag['tag']);
                $insertReq->execute();
                $lastId = $this->Db->lastInsertId();

                // now reference it
                $insertReq2->bindParam(':item_id', $tag['item_id'], PDO::PARAM_INT);
                $insertReq2->bindValue(':item_type', 'experiments');
                $insertReq2->bindParam(':tag_id', $lastId, PDO::PARAM_INT);
                $insertReq2->execute();
            } else {
                // tag exists, reference it for the entity
                $insertReq2->bindParam(':item_id', $tag['item_id'], PDO::PARAM_INT);
                $insertReq2->bindValue(':item_type', 'experiments');
                $insertReq2->bindParam(':tag_id', $res, PDO::PARAM_INT);
                $insertReq2->execute();
            }
        }

        foreach ($itemsTags as $tag) {
            // check if the tag doesn't exist already for the team
            $sql = 'SELECT id FROM tags WHERE tag = :tag AND team = :team';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':tag', $tag['tag']);
            $req->bindParam(':team', $tag['team_id'], PDO::PARAM_INT);
            $req->execute();
            $res = $req->fetchColumn();
            if ($req->rowCount() === 0) {
                // tag doesn't exist already
                $insertReq->bindParam(':team', $tag['team_id'], PDO::PARAM_INT);
                $insertReq->bindParam(':tag', $tag['tag']);
                $insertReq->execute();
                $lastId = $this->Db->lastInsertId();
                // now reference it
                $insertReq2->bindParam(':item_id', $tag['item_id'], PDO::PARAM_INT);
                $insertReq2->bindValue(':item_type', 'items');
                $insertReq2->bindParam(':tag_id', $lastId, PDO::PARAM_INT);
                $insertReq2->execute();
            } else {
                // get the id of the tag so we can insert it in the tags2entity table
                $insertReq2->bindParam(':item_id', $tag['item_id'], PDO::PARAM_INT);
                $insertReq2->bindValue(':item_type', 'items');
                $insertReq2->bindParam(':tag_id', $res, PDO::PARAM_INT);
                $insertReq2->execute();
            }
        }

        foreach ($tplTags as $tag) {
            // check if the tag doesn't exist already for the team
            $sql = 'SELECT id FROM tags WHERE tag = :tag AND team = :team';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':tag', $tag['tag']);
            $req->bindParam(':team', $tag['team'], PDO::PARAM_INT);
            $req->execute();
            $res = $req->fetchColumn();
            if ($req->rowCount() === 0) {
                // tag doesn't exist already
                $insertReq->bindParam(':team', $tag['team'], PDO::PARAM_INT);
                $insertReq->bindParam(':tag', $tag['tag']);
                $insertReq->execute();
                $lastId = $this->Db->lastInsertId();
                // now reference it
                $insertReq2->bindParam(':item_id', $tag['item_id'], PDO::PARAM_INT);
                $insertReq2->bindValue(':item_type', 'experiments_tpl');
                $insertReq2->bindParam(':tag_id', $lastId, PDO::PARAM_INT);
                $insertReq2->execute();
            } else {
                $insertReq2->bindParam(':item_id', $tag['item_id'], PDO::PARAM_INT);
                $insertReq2->bindValue(':item_type', 'experiments_tpl');
                $insertReq2->bindParam(':tag_id', $res, PDO::PARAM_INT);
                $insertReq2->execute();
            }
        }
    }
}

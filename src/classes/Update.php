<?php
/**
 * \Elabftw\Elabftw\Update
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Exception;
use FilesystemIterator;
use PDO;

/**
 * Use this to check for latest version or update the database schema
 */
class Update
{
    /** @var Db $Db SQL Database */
    private $Db;

    /** @var Config $Config instance of Config */
    public $Config;

    /**
     * /////////////////////////////////////////////////////
     * UPDATE THIS AFTER ADDING A BLOCK TO runUpdateScript()
     * AND REFLECT THE CHANGE IN tests/_data/phpunit.sql
     * AND src/sql/structure.sql
     * /////////////////////////////////////////////////////
     */
    private const REQUIRED_SCHEMA = 43;

    /**
     * Init Update with Config and Db
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->Config = $config;
        $this->Db = Db::getConnection();
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
     * Update the database schema if needed.
     * Returns true if there is no need to update
     *
     * @throws Exception
     * @return bool|string[] $msg_arr
     */
    public function runUpdateScript()
    {
        $current_schema = (int) $this->Config->configArr['schema'];

        if ($current_schema === self::REQUIRED_SCHEMA) {
            return true;
        }

        if ($current_schema < 37) {
            throw new Exception('Please update first to latest version from 1.8 branch before updating to 2.0 branch! See documentation.');
        }

        $msg_arr = array();

        if ($current_schema < 38) {
            // 20180402 v2.0.0
            $this->schema38();
            $this->updateSchema(38);
        }
        if ($current_schema < 39) {
            // 20180406 v2.0.0
            $this->schema39();
            $this->updateSchema(39);
        }
        if ($current_schema < 40) {
            // 20180513 v2.0.0
            $this->schema40();
            $this->updateSchema(40);
        }
        if ($current_schema < 41) {
            // 20180602 v2.0.0
            $this->schema41();
            $this->updateSchema(41);
        }
        if ($current_schema < 42) {
            // 20180716 v2.0.0
            $this->schema42();
            $this->updateSchema(42);
        }
        if ($current_schema < 43) {
            // 20180727 v2.0.0
            $this->schema43();
            $this->updateSchema(43);
        }
        // place new schema functions above this comment

        $this->cleanTmp();

        $msg_arr[] = '[SUCCESS] You are now running the latest version of eLabFTW. Have a great day! :)';

        return $msg_arr;
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
     * @throws Exception
     * @param int $schema the version we want to update
     * @return void
     */
    private function updateSchema(int $schema): void
    {
        $config_arr = array('schema' => $schema);
        if (!$this->Config->update($config_arr)) {
            throw new Exception('Failed at updating the schema number to: ' . $schema);
        }
    }

    /**
     * Add items_comments and rename exp_id to item_id in experiments_comments
     *
     * @throws Exception
     * @return void
     */
    private function schema38(): void
    {
        $sql = "ALTER TABLE experiments_comments CHANGE exp_id item_id INT(10) UNSIGNED NOT NULL";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 38!');
        }
        $sql = "CREATE TABLE IF NOT EXISTS `items_comments` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `datetime` datetime NOT NULL,
          `item_id` int(11) NOT NULL,
          `comment` text NOT NULL,
          `userid` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        );";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 38 (second part)!');
        }
    }

    /**
     * Remove can_lock from users table
     *
     * @throws Exception
     * @return void
     */
    private function schema39(): void
    {
        $sql = "ALTER TABLE `users` DROP `can_lock`";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 39!');
        }
    }

    /**
     * Add allow_edit to Users
     *
     * @throws Exception
     * @return void
     */
    private function schema40(): void
    {
        $sql = "ALTER TABLE `users` ADD `allow_edit` TINYINT(1) NOT NULL DEFAULT '0'";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 40!');
        }
    }

    /**
     * Merge the experiments_tags, items_tags and experiments_tpl_tags tables into tags and tags2entity tables.
     *
     * @throws Exception
     * @return void
     */
    private function schema41(): void
    {
        // first create the tags table
        $sql = "CREATE TABLE IF NOT EXISTS `tags` ( `id` INT NOT NULL AUTO_INCREMENT , `team` INT NOT NULL , `tag` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`))";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem creating table tags!');
        }

        // now create the mapping table
        $sql = "CREATE TABLE IF NOT EXISTS `tags2entity` ( `item_id` INT NOT NULL , `tag_id` INT NOT NULL , `item_type` VARCHAR(255) NOT NULL)";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem creating table tags2entity!');
        }

        // fetch existing tags
        $sql = "SELECT experiments_tags.*, users.team FROM experiments_tags INNER JOIN users ON (experiments_tags.userid = users.userid)";
        $req = $this->Db->prepare($sql);
        $req->execute();
        $experimentsTags = $req->fetchAll();

        // same for items tags
        $sql = "SELECT * FROM items_tags";
        $req = $this->Db->prepare($sql);
        $req->execute();
        $itemsTags = $req->fetchAll();

        // same for experiments_tpl_tags
        $sql = "SELECT experiments_tpl_tags.*, users.team FROM experiments_tpl_tags INNER JOIN users ON (experiments_tpl_tags.userid = users.userid)";
        $req = $this->Db->prepare($sql);
        $req->execute();
        $tplTags = $req->fetchAll();

        // now the insert part
        $insertSql = "INSERT INTO tags (team, tag) VALUES (:team, :tag)";
        $insertReq = $this->Db->prepare($insertSql);

        $insertSql2 = "INSERT INTO tags2entity (item_id, item_type, tag_id) VALUES (:item_id, :item_type, :tag_id)";
        $insertReq2 = $this->Db->prepare($insertSql2);

        foreach($experimentsTags as $tag) {
            // check if the tag doesn't exist already for the team
            $sql = "SELECT id FROM tags WHERE tag = :tag AND team = :team";
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

        foreach($itemsTags as $tag) {
            // check if the tag doesn't exist already for the team
            $sql = "SELECT id FROM tags WHERE tag = :tag AND team = :team";
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

        foreach($tplTags as $tag) {
            // check if the tag doesn't exist already for the team
            $sql = "SELECT id FROM tags WHERE tag = :tag AND team = :team";
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

    /**
     * Add visibility to Db items
     *
     * @throws Exception
     * @return void
     */
    private function schema42(): void
    {
        $sql = "ALTER TABLE `items` ADD `visibility` VARCHAR(255) NOT NULL DEFAULT 'team'";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem adding visibility to database items (schema 42)!');
        }
    }

    /**
     * Add open_science to config
     *
     * @throws Exception
     * @return void
     */
    private function schema43(): void
    {
        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES ('open_science', '0'), ('open_team', NULL);";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem adding open_science and open_team to config (schema 43)!');
        }
    }
}

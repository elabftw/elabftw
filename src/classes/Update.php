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
namespace Elabftw\Elabftw;

use Exception;
use FilesystemIterator;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key;

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
     * UPDATE IT ALSO IN INSTALL/ELABFTW.SQL (last line)
     * AND REFLECT THE CHANGE IN tests/_data/phpunit.sql
     * /////////////////////////////////////////////////////
     */
    private const REQUIRED_SCHEMA = 38;

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
            throw new Exception('Please update first to latest version from 1.8 branch before updating to 2.0 branch!');
        }

        $msg_arr = array();

        if ($current_schema < 38) {
            // 20180402 v2.0.0
            $this->schema38();
            $this->updateSchema(38);
        }
        // place new schema functions above this comment

        $this->cleanTmp();

        $msg_arr[] = '[SUCCESS] You are now running the latest version of eLabFTW. Have a great day! :)';

        return $msg_arr;
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
        $di = new \RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
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
}

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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Defuse\Crypto\Crypto as Crypto;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key as Key;

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
    const REQUIRED_SCHEMA = '37';

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
     * @return bool|string[] $msg_arr
     */
    public function runUpdateScript()
    {
        $current_schema = $this->Config->configArr['schema'];

        // fix for php56
        $required_schema = self::REQUIRED_SCHEMA;
        if ($current_schema === $required_schema) {
            return true;
        }

        $msg_arr = array();


        if ($current_schema < 2) {
            // 20150727
            $this->schema2();
            $this->updateSchema(2);
        }
        if ($current_schema < 3) {
            // 20150728
            $this->schema3();
            $this->updateSchema(3);
        }
        if ($current_schema < 4) {
            // 20150801
            $this->schema4();
            $this->updateSchema(4);
        }
        if ($current_schema < 5) {
            // 20150803
            $this->schema5();
            $this->updateSchema(5);
        }
        if ($current_schema < 6) {
            // 20160129
            $this->schema6();
            $this->updateSchema(6);
        }
        if ($current_schema < 7) {
            // 20160209
            $this->schema7();
            $this->updateSchema(7);
        }
        if ($current_schema < 8) {
            // 20160420
            $this->schema8();
            $this->updateSchema(8);
        }
        if ($current_schema < 9) {
            // 20160623
            $this->schema9();
            $this->updateSchema(9);
            $msg_arr[] = "[WARNING] The config file has been changed! If you are running Docker, make sure to copy your secret key to the yml file. Check the release notes!";
        }
        if ($current_schema < 10) {
            // 20160722
            $this->schema10();
            $this->updateSchema(10);
        }
        if ($current_schema < 11) {
            // 20160812
            $this->schema11();
            $this->updateSchema(11);
        }
        if ($current_schema < 12) {
            // 20161016
            $this->schema12();
            $this->updateSchema(12);
        }
        if ($current_schema < 13) {
            // 20161219
            $this->schema13();
            $this->updateSchema(13);
        }

        if ($current_schema < 14) {
            // 20170121
            $this->schema14();
            $this->updateSchema(14);
        }

        if ($current_schema < 15) {
            // 20170124
            $this->schema15();
            $this->updateSchema(15);
        }

        if ($current_schema < 16) {
            // 20170124
            $this->schema16();
            $this->updateSchema(16);
        }

        if ($current_schema < 17) {
            // 20170324
            // here we only want to empty the twig cache
            $this->updateSchema(17);
        }

        if ($current_schema < 18) {
            // 20170404
            // here we only want to empty the twig cache
            // maybe I should think of a better way than abusing the schema stuff
            // but for now it'll do. I mean it works, so why not.
            $this->updateSchema(18);
        }

        if ($current_schema < 19) {
            // 20170404
            // here we only want to empty the twig cache
            // maybe I should think of a better way than abusing the schema stuff
            // but for now it'll do. I mean it works, so why not.
            $this->updateSchema(19);
        }

        if ($current_schema < 20) {
            // 20170505
            $this->schema20();
            $this->updateSchema(20);
        }

        if ($current_schema < 21) {
            // 20170517
            $this->schema21();
            $this->updateSchema(21);
        }

        if ($current_schema < 22) {
            // 20170517
            $this->schema22();
            $this->updateSchema(22);
        }

        if ($current_schema < 23) {
            // 20170517
            $this->schema23();
            $this->updateSchema(23);
        }

        if ($current_schema < 24) {
            // 20170720
            $this->schema24();
            $this->updateSchema(24);
        }

        if ($current_schema < 25) {
            // 20170808
            $this->schema25();
            $this->updateSchema(25);
        }

        if ($current_schema < 26) {
            // 20170808
            $this->schema26();
            $this->updateSchema(26);
        }

        if ($current_schema < 27) {
            // 20170808
            $this->schema27();
            $this->updateSchema(27);
        }

        if ($current_schema < 28) {
            // 20170811
            $this->schema28();
            $this->updateSchema(28);
        }

        if ($current_schema < 29) {
            // 20170813
            $this->schema29();
            $this->updateSchema(29);
        }

        if ($current_schema < 30) {
            // 20170818
            $this->schema30();
            $this->updateSchema(30);
        }

        if ($current_schema < 31) {
            // 20170821
            $this->schema31();
            $this->updateSchema(31);
        }

        if ($current_schema < 32) {
            // 20170905
            // here we only want to empty the twig cache
            // maybe I should think of a better way than abusing the schema stuff
            // but for now it'll do. I mean it works, so why not.
            $this->updateSchema(32);
        }

        if ($current_schema < 33) {
            // 20170921
            // I think now it's time to find a better way to clean cache on update…
            $this->updateSchema(33);
        }

        if ($current_schema < 34) {
            // 20171106
            // I think now it's time to find a better way to clean cache on update…
            $this->updateSchema(34);
        }
        if ($current_schema < 35) {
            // 20171201
            $this->schema35();
            $this->updateSchema(35);
        }
        if ($current_schema < 36) {
            // 20171201
            $this->schema36();
            $this->updateSchema(36);
        }
        if ($current_schema < 37) {
            // 20180228
            $this->schema37();
            $this->updateSchema(37);
        }
        // place new schema functions above this comment

        // remove files in uploads/tmp
        $this->cleanTmp();

        $msg_arr[] = "[SUCCESS] You are now running the latest version of eLabFTW. Have a great day! :)";

        return $msg_arr;
    }

    /**
     * Delete things in the tmp folder
     */
    private function cleanTmp()
    {
        // cleanup files in tmp
        $dir = ELAB_ROOT . '/uploads/tmp';
        $di = new \RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
    }

    /**
     * Update the schema value in config to latest because we did the update functions before
     *
     * @param string|null $schema the version we want to update
     */
    private function updateSchema($schema = null)
    {
        if (is_null($schema)) {
            $schema = self::REQUIRED_SCHEMA;
        }
        $config_arr = array('schema' => $schema);
        if (!$this->Config->Update($config_arr)) {
            throw new Exception('Failed at updating the schema!');
        }
    }

    /**
     * Add a default value to deletable_xp.
     * Can't do the same for link_href and link_name because they are text
     *
     * @throws Exception if there is a problem
     */
    private function schema2()
    {
        $sql = "ALTER TABLE teams CHANGE deletable_xp deletable_xp TINYINT(1) NOT NULL DEFAULT '1'";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating!');
        }
    }

    /**
     * Change the experiments_revisions structure to allow code reuse
     *
     * @throws Exception if there is a problem
     */
    private function schema3()
    {
        $sql = "ALTER TABLE experiments_revisions CHANGE exp_id item_id INT(10) UNSIGNED NOT NULL";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating!');
        }
    }

    /**
     * Add user groups
     *
     * @throws Exception if there is a problem
     */
    private function schema4()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `team_groups` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , `team` INT UNSIGNED NOT NULL , PRIMARY KEY (`id`));";
        $sql2 = "CREATE TABLE IF NOT EXISTS `users2team_groups` ( `userid` INT UNSIGNED NOT NULL , `groupid` INT UNSIGNED NOT NULL );";
        if (!$this->Db->q($sql) || !$this->Db->q($sql2)) {
            throw new Exception('Problem updating!');
        }
    }

    /**
     * Switch the crypto lib to defuse/php-encryption
     *
     * EDIT 20160624: this function will not work now because of switch from 1.2 to 2.0
     * So tell the user to first update to 1.2.0-p3.
     * @throws Exception
     */
    private function schema5()
    {
        throw new Exception('Please update first to 1.2.0-p3 (git checkout 1.2.0-p3) before updating to the latest version.');
    }

    /**
     * Change column type of body in 'items' and 'experiments' to 'mediumtext'
     *
     * @throws Exception
     */
    private function schema6()
    {
        $sql = "ALTER TABLE experiments MODIFY body MEDIUMTEXT";
        $sql2 = "ALTER TABLE items MODIFY body MEDIUMTEXT";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot change type of column "body" in table "experiments"!');
        }
        if (!$this->Db->q($sql2)) {
            throw new Exception('Cannot change type of column "body" in table "items"!');
        }
    }

    /**
     * Change md5 to generic hash column in uploads
     * Create column to store the used algorithm type
     *
     * @throws Exception
     */
    private function schema7()
    {
        // First rename the column and then change its type to VARCHAR(128).
        // This column will be able to keep any sha2 hash up to sha512.
        // Add a hash_algorithm column to store the algorithm used to create
        // the hash.
        $sql3 = "ALTER TABLE `uploads` CHANGE `md5` `hash` VARCHAR(32);";
        if (!$this->Db->q($sql3)) {
            throw new Exception('Error renaming column "md5" in table "uploads"!');
        }
        $sql4 = "ALTER TABLE `uploads` MODIFY `hash` VARCHAR(128);";
        if (!$this->Db->q($sql4)) {
            throw new Exception('Error changing column type of "hash" in table "uploads"!');
        }
        // Already existing hashes are exclusively md5
        $sql5 = "ALTER TABLE `uploads` ADD `hash_algorithm` VARCHAR(10) DEFAULT NULL; UPDATE `uploads` SET `hash_algorithm`='md5' WHERE `hash` IS NOT NULL;";
        if (!$this->Db->q($sql5)) {
            throw new Exception('Error setting hash algorithm for existing entries!');
        }
    }

    /**
     * Remove username from users
     *
     * @throws Exception
     */
    private function schema8()
    {
        $sql = "ALTER TABLE `users` DROP `username`";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error removing username column');
        }
    }

    /**
     * Update the crypto lib to the latest version
     *
     * @throws Exception
     */
    private function schema9()
    {
        if (!is_writable(ELAB_ROOT . 'config.php')) {
            throw new Exception('Please make your config file writable by server for this update.');
        }
        // grab old key
        $legacy_key = hex2bin(SECRET_KEY);
        // make a new one too
        $new_key = Key::createNewRandomKey();

        // update smtp_password first
        if ($this->Config->configArr['smtp_password']) {
            try {
                $plaintext = Crypto::legacyDecrypt(hex2bin($this->Config->configArr['smtp_password']), $legacy_key);
            } catch (Ex\WrongKeyOrModifiedCiphertextException $ex) {
                throw new Exception('Wrong key or modified ciphertext error.');
            }
            // now encrypt it with the new method
            $new_ciphertext = Crypto::encrypt($plaintext, $new_key);
            $this->Config->update(array('smtp_password' => $new_ciphertext));
        }

        // now update the stamppass from the teams
        $sql = 'SELECT team_id, stamppass FROM teams';
        $req = $this->Db->prepare($sql);
        $req->execute();
        while ($teams = $req->fetch()) {
            if ($teams['stamppass']) {
                try {
                    $plaintext = Crypto::legacyDecrypt(hex2bin($teams['stamppass']), $legacy_key);
                } catch (Ex\WrongKeyOrModifiedCiphertextException $ex) {
                    throw new Exception('Wrong key or modified ciphertext error.');
                }
                $new_ciphertext = Crypto::encrypt($plaintext, $new_key);
                $sql = 'UPDATE teams SET stamppass = :stamppass WHERE team_id = :team_id';
                $update = $this->Db->prepare($sql);
                $update->bindParam(':stamppass', $new_ciphertext);
                $update->bindParam(':team_id', $teams['team_id']);
                $update->execute();
            }
        }

        // update the main stamppass
        if ($this->Config->configArr['stamppass']) {
            try {
                $plaintext = Crypto::legacyDecrypt(hex2bin($this->Config->configArr['stamppass']), $legacy_key);
            } catch (Ex\WrongKeyOrModifiedCiphertextException $ex) {
                throw new Exception('Wrong key or modified ciphertext error.');
            }
            // now encrypt it with the new method
            $new_ciphertext = Crypto::encrypt($plaintext, $new_key);
            $this->Config->update(array('stamppass' => $new_ciphertext));
        }

            // rewrite the config file with the new key
            $contents = "<?php
define('DB_HOST', '" . DB_HOST . "');
define('DB_NAME', '" . DB_NAME . "');
define('DB_USER', '" . DB_USER . "');
define('DB_PASSWORD', '" . DB_PASSWORD . "');
define('ELAB_ROOT', '" . ELAB_ROOT . "');
define('SECRET_KEY', '" . $new_key->saveToAsciiSafeString() . "');
";

        if (file_put_contents(ELAB_ROOT . 'config.php', $contents) == 'false') {
            throw new Exception('There was a problem writing the file!');
        }
    }

    /**
     * Add team calendar
     *
     */
    private function schema10()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `team_events` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT , `team` INT UNSIGNED NOT NULL , `item` INT UNSIGNED NOT NULL, `start` VARCHAR(255) NOT NULL, `end` VARCHAR(255), `title` VARCHAR(255) NULL DEFAULT NULL, `userid` INT UNSIGNED NOT NULL, PRIMARY KEY (`id`));";
        $sql2 = "ALTER TABLE `items_types` ADD `bookable` BOOL NULL DEFAULT FALSE";
        if (!$this->Db->q($sql) || !$this->Db->q($sql2)) {
            throw new Exception('Problem updating to schema 10!');
        }
    }

    /**
     * Add show_team in user prefs
     *
     */
    private function schema11()
    {
        $sql = "ALTER TABLE `users` ADD `show_team` TINYINT NOT NULL DEFAULT '0'";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 11!');
        }
    }
    /**
     * Change path to pki cert
     *
     */
    private function schema12()
    {
        if ($this->Config->configArr['stampcert'] === 'vendor/pki.dfn.pem') {
            if (!$this->Config->update(array('stampcert' => 'app/dfn-cert/pki.dfn.pem'))) {
                throw new Exception('Error changing path to timestamping cert. (updating to schema 12)');
            }
        }
    }

    /**
     * Add todolist table and update any old documentation link (local one)
     *
     */
    private function schema13()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `todolist` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `body` text NOT NULL,
          `creation_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ordering` int(10) UNSIGNED DEFAULT NULL,
          `userid` int(10) UNSIGNED NOT NULL,
          PRIMARY KEY (`id`));";

        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 13!');
        }

        // update the links. Use % because we might have index.html at the end
        $sql = "UPDATE teams
            SET link_href = 'https://doc.elabftw.net'
            WHERE link_href LIKE 'doc/_build/html%'";
        if (!$this->Db->q($sql)) {
            throw new Exception('Problem updating to schema 13!');
        }
    }

    /**
     * Make bgcolor be color
     *
     */
    private function schema14()
    {
        $sql = "ALTER TABLE `items_types` CHANGE `bgcolor` `color` VARCHAR(6)";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema14');
        }
    }

    /**
     * Add api key to users
     *
     */
    private function schema15()
    {
        $sql = "ALTER TABLE `users` ADD `api_key` VARCHAR(255) NULL DEFAULT NULL AFTER `show_team`;";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema15');
        }
    }
    /**
     * Add default_vis to users
     *
     */
    private function schema16()
    {
        $sql = "ALTER TABLE `users` ADD `default_vis` VARCHAR(255) NULL DEFAULT 'team';";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema16');
        }
    }

    /**
     * Add IDPs table for Identity Providers
     *
     */
    private function schema20()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `idps` (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(255) NOT NULL,
          `entityid` VARCHAR(255) NOT NULL,
          `sso_url` VARCHAR(255) NOT NULL,
          `sso_binding` VARCHAR(255) NOT NULL,
          `slo_url` VARCHAR(255) NOT NULL,
          `slo_binding` VARCHAR(255) NOT NULL,
          `x509` text NOT NULL,
          PRIMARY KEY (`id`));";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema20');
        }

        // add more config options for saml auth
        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('saml_debug', '0'),
            ('saml_strict', '1'),
            ('saml_baseurl', NULL),
            ('saml_entityid', NULL),
            ('saml_acs_url', NULL),
            ('saml_acs_binding', NULL),
            ('saml_slo_url', NULL),
            ('saml_slo_binding', NULL),
            ('saml_nameidformat', NULL),
            ('saml_x509', NULL),
            ('saml_privatekey', NULL)";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema20');
        }
    }

    /**
     * Remove the display option from users table because it's useless
     * Add field to store if a status may be timestamped
     * Already existing statuses may be timestamped by default
     *
     */
    private function schema21()
    {
        $sql = "ALTER TABLE `users` DROP `display`;";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema21');
        }
        $sql = "ALTER TABLE `status` ADD `is_timestampable` TINYINT(1) NOT NULL DEFAULT 1;";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema21');
        }
    }

    /**
     * Add local_register and local_login to config
     *
     */
    private function schema22()
    {
        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('local_register', '1'),
            ('local_login', '1'),
            ('saml_team', NULL),
            ('saml_email', NULL),
            ('saml_firstname', NULL),
            ('saml_lastname', NULL)";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error updating to schema22');
        }
    }

    /**
     * Change column type of body in 'items_revisions' and 'experiments_revisions' to 'mediumtext'
     * See elabftw/elabftw#429
     *
     * @throws Exception
     */
    private function schema23()
    {
        $sql = "ALTER TABLE experiments_revisions MODIFY body MEDIUMTEXT";
        $sql2 = "ALTER TABLE items_revisions MODIFY body MEDIUMTEXT";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot change type of column "body" in table "experiments_revisions"!');
        }
        if (!$this->Db->q($sql2)) {
            throw new Exception('Cannot change type of column "body" in table "items_revisions"!');
        }
    }

    /**
     * Add a column for team_orgid which is the ID of the team given by SAML auth
     *
     * @throws Exception
     */
    private function schema24()
    {
        $sql = "ALTER TABLE `teams` ADD `team_orgid` VARCHAR(255) NULL DEFAULT NULL;";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add team_orgid to teams table!');
        }
    }

    /**
     * Add a column for single user layout pref. See #410
     *
     * @throws Exception
     */
    private function schema25()
    {
        $sql = "ALTER TABLE `users` ADD `single_column_layout` TINYINT(1) NOT NULL DEFAULT 0;";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add single_column_layout to users table!');
        }
    }

    /**
     * Add a user preference for enabling CJK fonts for PDF generation. See #350
     *
     * @throws Exception
     */
    private function schema26()
    {
        $sql = "ALTER TABLE `users` ADD `cjk_fonts` TINYINT(1) NOT NULL DEFAULT 0;";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add cjk_fonts to users table!');
        }
    }

    /**
     * Add a user preference for orderby
     *
     * @throws Exception
     */
    private function schema27()
    {
        $sql = "ALTER TABLE `users` ADD `orderby` VARCHAR(255) NULL DEFAULT NULL;";
        $sql2 = "ALTER TABLE `users` ADD `sort` VARCHAR(255) NULL DEFAULT NULL;";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add orderby to users table!');
        }
        if (!$this->Db->q($sql2)) {
            throw new Exception('Cannot add sort to users table!');
        }
    }

    /**
     * Add experiments steps
     *
     * @throws Exception
     */
    private function schema28()
    {
        $sql = "CREATE TABLE `experiments_steps` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
            `item_id` INT UNSIGNED NOT NULL ,
            `body` TEXT NOT NULL ,
            `ordering` INT UNSIGNED NULL DEFAULT NULL ,
            `finished` TINYINT(1) NOT NULL DEFAULT '0',
            `finished_time` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`));";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add experiments_steps table!');
        }
    }

    /**
     * Add a user preference for disabling TinyMCE and using markdown
     *
     * @throws Exception
     */
    private function schema29()
    {
        $sql = "ALTER TABLE `users` ADD `use_markdown` TINYINT(1) NOT NULL DEFAULT 0;";

        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add use_markdown to users table!');
        }
    }

    /**
     * Some saml config entries were not added to the elabftw.sql install file
     * So this is to fix that
     */
    private function schema30()
    {
        if (!in_array('saml_email', array_keys($this->Config->configArr))) {
            $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('saml_email', NULL)";
            if (!$this->Db->q($sql)) {
                throw new Exception('Cannot add saml_email to config!');
            }
        }
        if (!in_array('saml_firstname', array_keys($this->Config->configArr))) {
            $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('saml_firstname', NULL)";
            if (!$this->Db->q($sql)) {
                throw new Exception('Cannot add saml_firstname to config!');
            }
        }
        if (!in_array('saml_lastname', array_keys($this->Config->configArr))) {
            $sql = "INSERT INTO config (conf_name, conf_value) VALUES ('saml_lastname', NULL)";
            if (!$this->Db->q($sql)) {
                throw new Exception('Cannot add saml_lastname to config!');
            }
        }
    }

    /**
     * Add tags to experiments_templates
     *
     */
    private function schema31()
    {
        $sql = "CREATE TABLE `experiments_tpl_tags` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `tag` VARCHAR(255) NOT NULL,
            `item_id` INT UNSIGNED NOT NULL,
            `userid` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`));";
        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot create experiments_tpl_tags table');
        }
    }

    /**
     * Add inc_files_pdf to users table
     *
     */
    private function schema35()
    {
        $sql = "ALTER TABLE `users` ADD `inc_files_pdf` TINYINT(1) NOT NULL DEFAULT '1';";
        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add inc_files_pdf to Users table');
        }
    }

    /**
     * Add public_db to teams and anon_users to config
     *
     */
    private function schema36()
    {
        $sql = "ALTER TABLE `teams` ADD `public_db` TINYINT(1) NOT NULL DEFAULT '0';";
        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add public_db to teams table');
        }
        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('anon_users', '0')";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error adding config anon_users');
        }
        $sql = "ALTER TABLE `users` ADD `archived` TINYINT(1) NOT NULL DEFAULT '0';";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error adding config anon_users');
        }
        $sql = "ALTER TABLE `users` ADD `pdfa` TINYINT(1) NOT NULL DEFAULT '1';";
        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add pdfa to users table!');
        }
        $sql = "ALTER TABLE `users` ADD `pdf_format` VARCHAR(255) NOT NULL DEFAULT 'A4';";
        if (!$this->Db->q($sql)) {
            throw new Exception('Cannot add pdf_format to users table!');
        }
    }

    /**
     * Add URL to config
     *
     */
    private function schema37()
    {
        $sql = "INSERT INTO `config` (`conf_name`, `conf_value`) VALUES
            ('url', NULL)";
        if (!$this->Db->q($sql)) {
            throw new Exception('Error adding config url');
        }
    }
}

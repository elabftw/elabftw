<?php
/**
 * \Elabftw\Elabftw\Update
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \FilesystemIterator;
use \Defuse\Crypto\Crypto as Crypto;

/**
 * Use this to check for latest version or update the database schema
 */
class Update
{
    /** 1.1.4 */
    private $version;
    /** the url line from the updates.ini file with link to archive */
    protected $url;
    /** sha512sum of the archive we should expect */
    protected $sha512;

    /** our favorite pdo object */
    private $pdo;

    /** this is used to check if we managed to get a version or not */
    public $success = false;

    /** where to get info from */
    const URL = 'https://get.elabftw.net/updates.ini';
    /** if we can't connect in https for some reason, use http */
    const URL_HTTP = 'http://get.elabftw.net/updates.ini';

    /**
     * ////////////////////////////
     * UPDATE THIS AFTER RELEASING
     * UPDATE IT ALSO IN doc/conf.py
     * AND package.json
     * ///////////////////////////
     */
    const INSTALLED_VERSION = '1.2.0-p1';

    /**
     * /////////////////////////////////////////////////////
     * UPDATE THIS AFTER ADDING A BLOCK TO runUpdateScript()
     * UPDATE IT ALSO IN INSTALL/ELABFTW.SQL (last line)
     * /////////////////////////////////////////////////////
     */
    const REQUIRED_SCHEMA = '8';

    /**
     * Create the pdo object
     *
     */
    public function __construct()
    {
        $this->pdo = Db::getConnection();
    }

    /**
     * Return the installed version of elabftw
     *
     * @return string
     */
    public function getInstalledVersion()
    {
        return self::INSTALLED_VERSION;
    }

    /**
     * Make a get request with cURL, using proxy setting if any
     *
     * @param string $url URL to hit
     * @param bool|string $toFile path where we want to save the file
     * @return string|boolean Return true if the download succeeded, else false
     */
    protected function get($url, $toFile = false)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('Please install php5-curl package.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // this is to get content
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // add proxy if there is one
        if (strlen(get_config('proxy')) > 0) {
            curl_setopt($ch, CURLOPT_PROXY, get_config('proxy'));
        }
        // disable certificate check
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // add user agent
        // http://developer.github.com/v3/#user-agent-required
        curl_setopt($ch, CURLOPT_USERAGENT, "Elabftw/" . self::INSTALLED_VERSION);

        // add a timeout, because if you need proxy, but don't have it, it will mess up things
        // 5 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        // we don't want the header
        curl_setopt($ch, CURLOPT_HEADER, 0);

        if ($toFile) {
            $handle = fopen($toFile, 'w');
            curl_setopt($ch, CURLOPT_FILE, $handle);
        }

        // DO IT!
        return curl_exec($ch);
    }

    /**
     * Return the latest version of elabftw
     * Will fetch updates.ini file from elabftw.net
     *
     * @throws Exception the version we have doesn't look like one
     * @return string|bool|null latest version or false if error
     */
    public function getUpdatesIni()
    {
        $ini = $this->get(self::URL);
        // try with http if https failed (see #176)
        if (!$ini) {
            $ini = $this->get(self::URL_HTTP);
        }
        // convert ini into array. The `true` is for process_sections: to get multidimensionnal array.
        $versions = parse_ini_string($ini, true);
        // get the latest version (first item in array, an array itself with url and checksum)
        $this->version = array_keys($versions)[0];
        $this->sha512 = substr($versions[$this->version]['sha512'], 0, 128);
        $this->url = $versions[$this->version]['url'];

        if (!$this->validateVersion()) {
            throw new Exception('Error getting latest version information from server!');
        }
        $this->success = true;
    }

    /**
     * Check if the version string actually looks like a version
     *
     * @return int 1 if version match
     */
    private function validateVersion()
    {
        return preg_match('/[0-99]+\.[0-99]+\.[0-99]+.*/', $this->version);
    }

    /**
     * Return true if there is a new version out there
     *
     * @return bool
     */
    public function updateIsAvailable()
    {
        return self::INSTALLED_VERSION != $this->version;
    }

    /**
     * Return the latest version string
     *
     * @return string|int 1.1.4
     */
    public function getLatestVersion()
    {
        return $this->version;
    }

    /**
     * Update the database schema if needed.
     *
     * @return string[] $msg_arr
     */
    public function runUpdateScript()
    {
        $current_schema = get_config('schema');
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

        // place new schema functions above this comment
        $this->cleanTmp();
        $msg_arr = array();
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
        if (!update_config($config_arr)) {
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
        if (!$this->pdo->q($sql)) {
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
        if (!$this->pdo->q($sql)) {
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
        if (!$this->pdo->q($sql) || !$this->pdo->q($sql2)) {
            throw new Exception('Problem updating!');
        }
    }

    /**
     * Switch the crypto lib to defuse/php-encryption
     *
     * @throws Exception
     */
    private function schema5()
    {
        if (!is_writable(ELAB_ROOT . 'config.php')) {
            throw new Exception('Please make your config file writable by server for this update.');
        }

        $legacy = new \Elabftw\Elabftw\LegacyCrypto();

        // our new key (raw binary string)
        try {
            $new_secret_key = Crypto::CreateNewRandomKey();
        } catch (Exception $e) {
            die($e->getMessage());
        }

        $new_smtp_password = '';
        $new_stamp_password = '';

        if (strlen(get_config('smtp_password')) > 0) {
            $old_smtp_password = $legacy->decrypt(get_config('smtp_password'));
            $new_smtp_password = Crypto::binTohex(Crypto::encrypt($old_smtp_password, $new_secret_key));
        }

        if (strlen(get_config('stamppass')) > 0) {
            // get the old passwords
            $old_stamp_password = $legacy->decrypt(get_config('stamppass'));
            $new_stamp_password = Crypto::binTohex(Crypto::encrypt($old_stamp_password, $new_secret_key));
        }

        $updates = array(
            'smtp_password' => $new_smtp_password,
            'stamppass' => $new_stamp_password
        );

        if (!update_config($updates)) {
            throw new Exception('Error updating config with new passwords!');
        }

        // we will rewrite the config file with the new key
        $contents = "<?php
define('DB_HOST', '" . DB_HOST . "');
define('DB_NAME', '" . DB_NAME . "');
define('DB_USER', '" . DB_USER . "');
define('DB_PASSWORD', '" . DB_PASSWORD . "');
define('ELAB_ROOT', '" . ELAB_ROOT . "');
define('SECRET_KEY', '" . Crypto::binTohex($new_secret_key) . "');
";

        if (file_put_contents('config.php', $contents) == 'false') {
            throw new Exception('There was a problem writing the file!');
        }
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

        if (!$this->pdo->q($sql)) {
            throw new Exception('Cannot change type of column "body" in table "experiments"!');
        }
        if (!$this->pdo->q($sql2)) {
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
        if (!$this->pdo->q($sql3)) {
            throw new Exception('Error renaming column "md5" in table "uploads"!');
        }
        $sql4 = "ALTER TABLE `uploads` MODIFY `hash` VARCHAR(128);";
        if (!$this->pdo->q($sql4)) {
            throw new Exception('Error changing column type of "hash" in table "uploads"!');
        }
        // Already existing hashes are exclusively md5
        $sql5 = "ALTER TABLE `uploads` ADD `hash_algorithm` VARCHAR(10) DEFAULT NULL; UPDATE `uploads` SET `hash_algorithm`='md5' WHERE `hash` IS NOT NULL;";
        if (!$this->pdo->q($sql5)) {
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
        if (!$this->pdo->q($sql)) {
            throw new Exception('Error removing username column');
        }
    }
}

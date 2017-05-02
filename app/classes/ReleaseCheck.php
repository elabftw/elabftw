<?php
/**
 * \Elabftw\Elabftw\ReleaseCheck
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * Use this to check for latest version
 */
class ReleaseCheck
{
    /** instance of Config */
    private $Config;

    /** the latest version from ini file (1.1.4) */
    private $version;

    /** release date of the version */
    private $releaseDate;

    /** this is used to check if we managed to get a version or not */
    public $success = false;

    /** where to get info from */
    const URL = 'https://get.elabftw.net/updates.ini';
    /** if we can't connect in https for some reason, use http */
    const URL_HTTP = 'http://get.elabftw.net/updates.ini';

    /**
     * ////////////////////////////
     * UPDATE THIS AFTER RELEASING
     * UPDATE IT ALSO IN package.json
     * ///////////////////////////
     */
    const INSTALLED_VERSION = '1.6.0';

    /**
     * Fetch the update info on object creation
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->Config = $config;
    }

    /**
     * Make a get request with cURL, using proxy setting if any
     *
     * @param string $url URL to hit
     * @return string|boolean Return true if the download succeeded, else false
     */
    private function get($url)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('Error: cURL PHP extension not loaded! Please install the PHP cURL extension.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // this is to get content
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // add proxy if there is one
        if (strlen($this->Config->configArr['proxy']) > 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->Config->configArr['proxy']);
        }

        // add user agent
        // http://developer.github.com/v3/#user-agent-required
        curl_setopt($ch, CURLOPT_USERAGENT, "Elabftw/" . self::INSTALLED_VERSION);

        // add a timeout, because if you need proxy, but don't have it, it will mess up things
        // 5 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        // we don't want the header
        curl_setopt($ch, CURLOPT_HEADER, 0);

        // DO IT!
        return curl_exec($ch);
    }

    /**
     * Return the latest version of elabftw
     * Will fetch updates.ini file from elabftw.net
     *
     * @return bool latest version or false if error
     */
    public function getUpdatesIni()
    {
        $ini = $this->get(self::URL);
        // try with http if https failed (see #176)
        if (!$ini) {
            $ini = $this->get(self::URL_HTTP);
        }
        if (!$ini) {
            return false;
        }
        // convert ini into array. The `true` is for process_sections: to get multidimensionnal array.
        $versions = parse_ini_string($ini, true);
        // get the latest version
        $this->version = array_keys($versions)[0];
        $this->releaseDate = $versions[$this->version]['date'];

        if (!$this->validateVersion()) {
            return false;
        }
        $this->success = true;
        return true;
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
     * Get when the latest version was released
     *
     * @return string
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

    /**
     * Get the documentation link for the changelog button
     *
     * @return string URL for changelog
     */
    public function getChangelogLink()
    {
        $base = "https://elabftw.readthedocs.io/en/latest/changelog.html#version-";
        $dashedVersion = str_replace(".", "-", $this->version);

        return $base . $dashedVersion;
    }
}

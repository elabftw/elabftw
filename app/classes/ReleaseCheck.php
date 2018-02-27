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
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

/**
 * Use this to check for latest version
 */
class ReleaseCheck
{
    /** @var Config $Config instance of Config */
    private $Config;

    /** @var string $version the latest version from ini file (1.1.4) */
    private $version;

    /** @var string $releaseDate release date of the version */
    private $releaseDate;

    /** @var bool $success this is used to check if we managed to get a version or not */
    public $success = false;

    /** where to get info from */
    const URL = 'https://get.elabftw.net/updates.ini';

    /** if we can't connect in https for some reason, use http */
    const URL_HTTP = 'http://get.elabftw.net/updates.ini';

    /**
     * ////////////////////////////
     * UPDATE THIS AFTER RELEASING
     * ///////////////////////////
     */
    const INSTALLED_VERSION = '1.8.3';

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
     * Make a GET request with Guzzle
     *
     * @param string $url URL to hit
     * @throws \GuzzleHttp\Exception\RequestException
     * @return \GuzzleHttp\Psr7\Response
     */
    private function get($url)
    {
        $client = new \GuzzleHttp\Client();

        return $client->request('GET', $url, [
            // add user agent
            // http://developer.github.com/v3/#user-agent-required
            'headers' => [
                'User-Agent' => 'Elabftw/' . self::INSTALLED_VERSION
            ],
            // add proxy if there is one
            'proxy' => $this->Config->configArr['proxy'],
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            // in seconds
            'timeout' => 5

        ]);
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
     * Return the latest version of elabftw
     * Will fetch updates.ini file from elabftw.net
     *
     * @return bool latest version or false if error
     */
    public function getUpdatesIni()
    {
        try {
            $response = $this->get(self::URL);
        } catch (RequestException $e) {
            // try with http if https failed (see #176)
            try {
                $response = $this->get(self::URL_HTTP);
            } catch (RequestException $e) {
                return false;
            }
        }

        // read the response
        $versions = parse_ini_string($response->getBody(), true);
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
        $base = "https://doc.elabftw.net/changelog.html#version-";
        $dashedVersion = str_replace(".", "-", $this->version);

        return $base . $dashedVersion;
    }
}

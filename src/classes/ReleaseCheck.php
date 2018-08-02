<?php
/**
 * \Elabftw\Elabftw\ReleaseCheck
 *
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use GuzzleHttp\Exception\RequestException;
use RuntimeException;

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
    private const URL = 'https://get.elabftw.net/updates.ini';

    /** if we can't connect in https for some reason, use http */
    private const URL_HTTP = 'http://get.elabftw.net/updates.ini';

    /**
     * ////////////////////////////
     * UPDATE THIS AFTER RELEASING
     * ///////////////////////////
     */
    public const INSTALLED_VERSION = '2.0.0-beta';

    /**
     * Fetch the update info on object creation
     *
     * @param Config $config Instance of Config
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function get($url): \Psr\Http\Message\ResponseInterface
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
    private function validateVersion(): int
    {
        $res = preg_match('/[0-99]+\.[0-99]+\.[0-99]+.*/', $this->version);
        if ($res === false) {
            throw new RuntimeException('Could not parse version!');
        }
        return $res;
    }

    /**
     * Try to get the latest version number of elabftw
     * Will fetch updates.ini file from get.elabftw.net
     *
     * @return bool
     */
    public function getUpdatesIni(): bool
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
        $versions = parse_ini_string((string) $response->getBody(), true);
        if ($versions === false) {
            return false;
        }
        // get the latest version
        $this->version = array_keys($versions)[0];
        $this->releaseDate = $versions[$this->version]['date'];

        if (!$this->validateVersion()) {
            return false;
        }
        $this->success = true;
        return $this->success;
    }

    /**
     * Return true if there is a new version out there
     *
     * @return bool
     */
    public function updateIsAvailable(): bool
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
    public function getReleaseDate(): string
    {
        return $this->releaseDate;
    }

    /**
     * Get the documentation link for the changelog button
     *
     * @return string URL for changelog
     */
    public function getChangelogLink(): string
    {
        $base = "https://doc.elabftw.net/changelog.html#version-";
        $dashedVersion = str_replace(".", "-", $this->version);

        return $base . $dashedVersion;
    }
}

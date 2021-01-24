<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ReleaseCheckException;
use Elabftw\Models\Config;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Use this to check for latest version
 */
class ReleaseCheck
{
    /** @var string INSTALLED_VERSION the current version of elabftw */
    public const INSTALLED_VERSION = '3.6.5';

    /** @var string $URL this file contains the latest version information */
    private const URL = 'https://get.elabftw.net/updates.ini';

    /** @var string URL_HTTP if we can't connect in https for some reason, use http */
    private const URL_HTTP = 'http://get.elabftw.net/updates.ini';

    /** @var bool $success this is used to check if we managed to get a version or not */
    public $success = false;

    /** @var Config $Config instance of Config */
    private $Config;

    /** @var string $version the latest version from ini file (1.1.4) */
    private $version = '';

    /** @var string $releaseDate release date of the version */
    private $releaseDate = '';

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
     * Try to get the latest version number of elabftw
     * Will fetch updates.ini file from get.elabftw.net
     *
     * @return void
     */
    public function getUpdatesIni(): void
    {
        try {
            $response = $this->get(self::URL);
        } catch (ConnectException | RequestException $e) {
            // try with http if https failed (see #176)
            try {
                $response = $this->get(self::URL_HTTP);
            } catch (ConnectException | RequestException $e) {
                throw new ReleaseCheckException('Could not make request to server!', (int) $e->getCode(), $e);
            }
        }

        // read the response
        $versions = parse_ini_string((string) $response->getBody(), true);
        if ($versions === false) {
            throw new ReleaseCheckException('Could not parse version!');
        }
        // get the latest version
        $this->version = array_keys($versions)[0];
        $this->releaseDate = $versions[$this->version]['date'];

        $this->validateVersion();
        // set this so we know the request was successful
        $this->success = true;
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
        $base = 'https://doc.elabftw.net/changelog.html#version-';
        $dashedVersion = str_replace('.', '-', $this->version);

        return $base . $dashedVersion;
    }

    /**
     * Make a GET request with Guzzle
     */
    private function get(string $url): ResponseInterface
    {
        $client = new \GuzzleHttp\Client();

        return $client->request('GET', $url, array(
            // add user agent
            // http://developer.github.com/v3/#user-agent-required
            'headers' => array(
                'User-Agent' => 'Elabftw/' . self::INSTALLED_VERSION,
            ),
            // add proxy if there is one
            'proxy' => $this->Config->configArr['proxy'],
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            // in seconds
            'timeout' => 4,
        ));
    }

    /**
     * Check if the version string actually looks like a version
     *
     * @return void
     */
    private function validateVersion(): void
    {
        $res = preg_match('/^[0-99]+\.[0-99]+\.[0-99]+.*$/', $this->version);
        if ($res === false) {
            throw new ReleaseCheckException('Could not parse version!');
        }
    }
}

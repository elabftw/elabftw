<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * HTTP wrapper
 * @final mocked in tests
 */
class HttpGetter
{
    private const int REQUEST_TIMEOUT = 100;

    private const int SUCCESS = 200;

    public function __construct(public Client $client, private string $proxy = '', private bool $verifyTls = true) {}

    public function get(string $url): string
    {
        try {
            $res = $this->client->get($url, array(
                // add proxy if there is one
                'proxy' => $this->proxy,
                'timeout' => self::REQUEST_TIMEOUT,
                'verify' => $this->verifyTls,
            ));
        } catch (ConnectException $e) {
            throw new ImproperActionException(sprintf('Error connecting to remote server: %s', $url), $e->getCode(), $e);
        }
        if ($res->getStatusCode() !== self::SUCCESS) {
            throw new ImproperActionException(sprintf('Error fetching remote content (%d).', $res->getStatusCode()));
        }
        return (string) $res->getBody();
    }

    public function postJson(string $url, array $json, array $headers = array()): string
    {
        try {
            $res = $this->client->post($url, array(
                // add proxy if there is one
                'proxy' => $this->proxy,
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => $headers,
                'json' => $json,
                'verify' => $this->verifyTls,
            ));
        } catch (ConnectException $e) {
            throw new ImproperActionException(sprintf('Error connecting to remote server: %s', $url), $e->getCode(), $e);
        }
        if ($res->getStatusCode() !== self::SUCCESS) {
            throw new ImproperActionException(sprintf('Error fetching remote content (%d).', $res->getStatusCode()));
        }
        return $res->getBody()->getContents();
    }
}

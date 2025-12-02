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
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP wrapper
 * @final mocked in tests
 */
class HttpGetter
{
    private const int REQUEST_TIMEOUT = 100;

    private const int SUCCESS = 200;

    private const int SUCCESS_NO_CONTENT = 204;

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

    public function getWithHeaders(string $url, array $headers = array()): array
    {
        try {
            $res = $this->client->get($url, array(
                'proxy' => $this->proxy,
                'timeout' => self::REQUEST_TIMEOUT,
                'verify' => $this->verifyTls,
                'headers' => $headers,
            ));
        } catch (ConnectException $e) {
            throw new ImproperActionException(sprintf('Error connecting to remote server: %s', $url), $e->getCode(), $e);
        }
        $status = $res->getStatusCode();
        if (!in_array($status, array(self::SUCCESS, self::SUCCESS_NO_CONTENT), true)) {
            throw new ImproperActionException(sprintf('Error fetching remote content (%d).', $status));
        }
        return array(
            'status' => $status,
            'headers' => $res->getHeaders(),
            'body' => (string) $res->getBody(),
        );
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

    public function post(string $url, array $options = array()): ResponseInterface
    {
        try {
            $res = $this->client->post($url, array_merge(
                array(
                    'proxy'   => $this->proxy,
                    'timeout' => self::REQUEST_TIMEOUT,
                    'verify'  => $this->verifyTls,
                ),
                $options,
            ));
        } catch (ConnectException $e) {
            throw new ImproperActionException(
                sprintf('Error connecting to remote server: %s', $url),
                $e->getCode(),
                $e
            );
        }
        return $res;
    }
}

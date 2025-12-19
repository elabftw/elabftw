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

    public function get(string $url, ?array $headers = array()): ResponseInterface
    {
        $options = array(
            'proxy'   => $this->proxy,
            'timeout' => self::REQUEST_TIMEOUT,
            'verify'  => $this->verifyTls,
        );
        if ($headers !== null) {
            $options['headers'] = $headers;
        }
        try {
            $res = $this->client->get($url, $options);
        } catch (ConnectException $e) {
            throw new ImproperActionException(sprintf('Error connecting to remote server: %s', $url), $e->getCode(), $e);
        }
        $status = $res->getStatusCode();
        if (!in_array($status, array(self::SUCCESS, self::SUCCESS_NO_CONTENT), true)) {
            throw new ImproperActionException(sprintf('Error fetching remote content (%d).', $res->getStatusCode()));
        }
        return $res;
    }

    public function post(string $url, array $options = array()): ResponseInterface
    {
        try {
            $res = $this->client->post($url, array_merge(
                array(
                    'proxy' => $this->proxy,
                    'timeout' => self::REQUEST_TIMEOUT,
                    'verify' => $this->verifyTls,
                ),
                $options,
            ));
        } catch (ConnectException $e) {
            throw new ImproperActionException(sprintf('Error connecting to remote server: %s', $url), $e->getCode(), $e);
        }
        return $res;
    }

    public function patch(string $url, array $options = array()): ResponseInterface
    {
        try {
            $res = $this->client->request('PATCH', $url, array_merge(
                array(
                    'proxy' => $this->proxy,
                    'timeout' => self::REQUEST_TIMEOUT,
                    'verify' => $this->verifyTls,
                ),
                $options,
            ));
        } catch (ConnectException $e) {
            throw new ImproperActionException(sprintf('Error connecting to remote server: %s', $url), $e->getCode(), $e);
        }
        return $res;
    }
}

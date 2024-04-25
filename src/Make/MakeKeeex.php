<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\App;
use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7;

/**
 * Keeex a file
 */
class MakeKeeex
{
    private const int REQUEST_TIMEOUT_SECONDS = 5;

    private string $url;

    public function __construct(
        private ClientInterface $client,
        private string $proxy = '',
        string $host = 'keeex',
        int $port = 8080,
    ) {
        $this->url = sprintf('http://%s:%d/keeex', $host, $port);
    }

    public function fromString(string $contents): string
    {
        return (string) $this->sendRequest($contents)->getBody();
    }

    private function sendRequest(string $contents): \Psr\Http\Message\ResponseInterface
    {
        $stream = Psr7\Utils::streamFor($contents);
        $options = array(
            'headers' => array(
                // add user agent, because we're polite
                'User-Agent' => 'Elabftw/' . App::INSTALLED_VERSION,
                // Note: don't send the Content-Type header with 'multipart/form-data' as the boundary will not be properly set
                // see: https://github.com/guzzle/guzzle/issues/1885
            ),
            // add proxy if there is one
            'proxy' => $this->proxy,
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            'timeout' => self::REQUEST_TIMEOUT_SECONDS,
            'multipart' => array(
                array(
                    'name'     => 'file',
                    'contents' => $stream->getContents(),
                    // without this parameter, it fails
                    'filename' => 'osef.pdf',
                ),
                array(
                    'name' => 'data',
                    'contents' => '{"src":"-","dst":"-","mdata":{"identities":[{"type":"default"}]},"options":{"timestamp":true}}',
                ),
            ),
        );

        try {
            return $this->client->request('POST', $this->url, $options);
        } catch (ClientException | ServerException $e) {
            throw new ImproperActionException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

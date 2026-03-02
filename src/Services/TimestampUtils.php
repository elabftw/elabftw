<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\App;
use Elabftw\Elabftw\FsTools;
use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Traits\ProcessTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FilesystemOperator;

use function is_readable;

/**
 * Trusted Timestamping (RFC3161) utility class
 */
final class TimestampUtils
{
    use ProcessTrait;

    private const TIMEOUT_SECONDS = 30;

    private array $trash = array();

    private FilesystemOperator $cacheFs;

    public function __construct(
        private ClientInterface $client,
        string $data,
        private array $tsConfig,
        private TimestampResponse $tsResponse,
    ) {
        // save the data inside a temporary file so openssl can act on it
        $this->cacheFs = Storage::CACHE->getStorage()->getFs();
        $this->tsResponse = new TimestampResponse();
        $this->cacheFs->write(basename($this->tsResponse->dataPath), $data);
        $this->trash[] = basename($this->tsResponse->dataPath);
    }

    /**
     * Delete all temporary files once the process is completed
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            $this->cacheFs->delete($file);
        }
    }

    /**
     * Do the timestamp, verify it and return path to saved token on disk along with extracted timestamp
     */
    public function timestamp(): TimestampResponse
    {
        $requestFilePath = $this->createRequestfile();
        $response = $this->postData($requestFilePath);
        // save token to (temporary) file
        $this->cacheFs->write(basename($this->tsResponse->tokenPath), $response->getBody()->getContents());
        $this->verify();
        return $this->tsResponse;
    }

    /**
     * Create a temporary Timestamp Requestfile from a file
     */
    private function createRequestfile(): string
    {
        $requestFilePath = FsTools::getCacheFile();

        $this->runProcess(array(
            'openssl',
            'ts',
            '-query',
            '-data',
            $this->tsResponse->dataPath,
            '-cert',
            '-' . $this->tsConfig['ts_hash'],
            '-no_nonce',
            '-out',
            $requestFilePath,
        ));
        // remove this file once we are done
        $this->trash[] = basename($requestFilePath);
        return $requestFilePath;
    }

    /**
     * Contact the TSA and receive a token after successful timestamp
     */
    private function postData(string $requestFilePath): \Psr\Http\Message\ResponseInterface
    {
        $options = array(
            // add user agent
            // http://developer.github.com/v3/#user-agent-required
            'headers' => array(
                'User-Agent' => 'Elabftw/' . App::INSTALLED_VERSION,
                'Content-Type' => 'application/timestamp-query',
                'Content-Transfer-Encoding' => 'base64',
            ),
            // add proxy if there is one
            'proxy' => Config::getConfig()->configArr['proxy'] ?? '',
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            'timeout' => self::TIMEOUT_SECONDS,
            'body' => file_get_contents($requestFilePath),
        );

        if ($this->tsConfig['ts_login'] && $this->tsConfig['ts_password']) {
            $options['auth'] = array(
                $this->tsConfig['ts_login'],
                $this->tsConfig['ts_password'],
            );
        }

        try {
            return $this->client->request('POST', $this->tsConfig['ts_url'], $options);
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function verify(): bool
    {
        if (!is_readable($this->tsConfig['ts_chain'])) {
            // no readable certificate chain means we don't do the verification
            return false;
        }

        $this->runProcess(array(
            'openssl',
            'ts',
            '-verify',
            // skip cert validity check
            '-no_check_time',
            '-data',
            $this->tsResponse->dataPath,
            '-in',
            $this->tsResponse->tokenPath,
            '-CAfile',
            $this->tsConfig['ts_chain'],
            '-untrusted',
            $this->tsConfig['ts_cert'],
        ));
        return true;
    }
}

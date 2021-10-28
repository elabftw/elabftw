<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\App;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\TimestampResponseInterface;
use Elabftw\Traits\ProcessTrait;
use Elabftw\Traits\UploadTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use function is_readable;
use Psr\Http\Message\StreamInterface;

/**
 * Trusted Timestamping (RFC3161) utility class
 */
class TimestampUtils
{
    use ProcessTrait;
    use UploadTrait;

    private array $trash = array();

    public function __construct(
        private ClientInterface $client,
        private string $dataPath,
        private array $tsConfig,
        private TimestampResponseInterface $tsResponse
    ) {
    }

    /**
     * Delete all temporary files once the processus is completed
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            unlink($file);
        }
    }

    /**
     * Do the timestamp, verify it and return path to saved token on disk along with extracted timestamp
     */
    public function timestamp(): TimestampResponseInterface
    {
        $requestFilePath = $this->createRequestfile();
        $response = $this->postData($requestFilePath);
        $this->saveToken($response->getBody());
        $this->verify();
        return $this->tsResponse;
    }

    private function saveToken(StreamInterface $binaryToken): void
    {
        $longName = $this->getLongName() . '.asn1';
        $filePath = $this->getUploadsPath() . $longName;
        $dir = dirname($filePath);
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
        }
        if (!file_put_contents($filePath, $binaryToken)) {
            throw new FilesystemErrorException('Cannot save token to disk!');
        }
        $this->tsResponse->setTokenPath($filePath);
        $this->tsResponse->setTokenName($longName);
    }

    /**
     * Create a temporary Timestamp Requestfile from a file
     */
    private function createRequestfile(): string
    {
        $requestFilePath = $this->getTmpPath() . $this->getUniqueString();

        $this->runProcess(array(
            'openssl',
            'ts',
            '-query',
            '-data',
            $this->dataPath,
            '-cert',
            '-' . $this->tsConfig['ts_hash'],
            '-no_nonce',
            '-out',
            $requestFilePath,
        ));
        // remove this file once we are done
        $this->trash[] = $requestFilePath;
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
            'proxy' => $this->tsConfig['proxy'] ?? '',
            // add a timeout, because if you need proxy, but don't have it, it will mess up things
            // in seconds
            'timeout' => 5,
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
            throw new ImproperActionException($e->getMessage(), (int) $e->getCode(), $e);
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
            '-data',
            $this->dataPath,
            '-in',
            $this->tsResponse->getTokenPath(),
            '-CAfile',
            $this->tsConfig['ts_chain'],
            '-untrusted',
            $this->tsConfig['ts_cert'],
        ));
        // a ProcessFailedException will be thrown if it fails
        return true;
    }
}

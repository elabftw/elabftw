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
use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\TimestampResponseInterface;
use Elabftw\Models\Config;
use Elabftw\Traits\ProcessTrait;
use Elabftw\Traits\UploadTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use function is_readable;
use League\Flysystem\FilesystemOperator;
use Psr\Http\Message\StreamInterface;

/**
 * Trusted Timestamping (RFC3161) utility class
 */
class TimestampUtils
{
    use ProcessTrait;
    use UploadTrait;

    private array $trash = array();

    private FilesystemOperator $cacheFs;

    // the path to a file with data to be timestamped
    private string $dataPath;

    public function __construct(
        private ClientInterface $client,
        string $data,
        private array $tsConfig,
        private TimestampResponseInterface $tsResponse
    ) {
        // save the data inside a temporary file so openssl can act on it
        $pdfPath = FsTools::getCacheFile() . '.pdf';
        $this->cacheFs = (new StorageFactory(StorageFactory::CACHE))->getStorage()->getFs();
        $this->cacheFs->write(basename($pdfPath), $data);
        $this->dataPath = $pdfPath;
        $this->trash[] = basename($this->dataPath);
    }

    /**
     * Delete all temporary files once the processus is completed
     */
    public function __destruct()
    {
        foreach ($this->trash as $file) {
            $this->cacheFs->delete($file);
        }
    }

    public function getDataPath(): string
    {
        return $this->dataPath;
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
        $filePath = FsTools::getCacheFile() . '.asn1';
        $this->cacheFs->write(basename($filePath), $binaryToken->getContents());

        $this->tsResponse->setTokenPath($filePath);
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
            $this->dataPath,
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

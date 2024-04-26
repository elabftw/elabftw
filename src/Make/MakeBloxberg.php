<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2015, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Elabftw\CreateImmutableArchivedUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\ExportFormat;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Traits\UploadTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use ZipArchive;

use function json_decode;
use function json_encode;

/**
 * Send data to Bloxberg server
 * elabid is submitted as the 'author' attribute
 */
class MakeBloxberg extends AbstractMakeTimestamp
{
    use UploadTrait;

    /**
     * This pubkey is currently the same for everyone
     * Information about the user/institution is stored in the metadataJson field
     */
    private const string PUB_KEY = '0xc4d84f32cd6fd05e2e292c171f5209a678525002';

    private const string CERT_URL = 'https://certify.bloxberg.org/createBloxbergCertificate';

    private const string PROOF_URL = 'https://certify.bloxberg.org/generatePDF';

    private const string API_KEY_URL = 'https://get.elabftw.net/?bloxbergapikey';

    private string $apiKey;

    public function __construct(protected array $configArr, AbstractConcreteEntity $entity, private Client $client)
    {
        parent::__construct($configArr, $entity, ExportFormat::Json);
        if ($configArr['blox_enabled'] !== '1') {
            throw new ImproperActionException('Bloxberg timestamping is disabled on this instance.');
        }
        $this->apiKey = $this->getApiKey();
    }

    public function timestamp(): int
    {
        $data = $this->generateData();
        $dataHash = hash('sha256', $data);

        try {
            // first request sends the hash to the certify endpoint
            $certifyResponse = json_decode($this->certify($dataHash));
            // now we send the previous response to another endpoint to get the pdf back in a zip archive
            $proofResponse = $this->client->post(self::PROOF_URL, array(
                // add proxy if there is one
                'proxy' => $this->configArr['proxy'] ?? '',
                'headers' => array(
                    'api_key' => $this->apiKey,
                ),
                'json' => $certifyResponse,
            ));
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), $e->getCode(), $e);
        }

        // the binary response is a zip archive that contains the certificate in pdf format
        $zip = $proofResponse->getBody()->getContents();
        // add the data to the zipfile and get the path to where it is stored in cache
        $tmpFilePath = $this->addToZip($zip, $data);
        // update timestamp on the entry
        $this->updateTimestamp(date('Y-m-d H:i:s'));
        // save the zip file as an upload
        return $this->Entity->Uploads->create(
            new CreateImmutableArchivedUpload(
                $this->getFileName(),
                $tmpFilePath,
                sprintf(_('Timestamp archive by %s'), $this->Entity->Users->userData['fullname'])
            )
        );
    }

    private function getApiKey(): string
    {
        try {
            $res = $this->client->get(self::API_KEY_URL, array(
                // add proxy if there is one
                'proxy' => $this->configArr['proxy'] ?? '',
                'timeout' => 5,
            ));
        } catch (ConnectException) {
            throw new ImproperActionException('Could not fetch api key. Please try again later.');
        }
        if ($res->getStatusCode() !== 200) {
            throw new ImproperActionException('Could not fetch api key. Please try again later.');
        }
        return (string) $res->getBody();
    }

    private function certify(string $hash): string
    {
        // in order to be GDPR compliant, it is possible to anonymize the author
        $author = $this->Entity->entityData['fullname'];
        if ($this->configArr['blox_anon']) {
            $author = 'eLabFTW user';
        }

        $options = array(
            'headers' => array(
                'api_key' => $this->apiKey,
            ),
            // add proxy if there is one
            'proxy' => $this->configArr['proxy'] ?? '',
            'json' => array(
                'publicKey' => self::PUB_KEY,
                'crid' => array('0x' . $hash),
                'cridType' => 'sha2-256',
                'enableIPFS' => false,
                'metadataJson' => json_encode(array(
                    'author' => $author,
                    'elabid' => $this->Entity->entityData['elabid'],
                    'instanceid' => 'not implemented',
                ), JSON_THROW_ON_ERROR, 512),
            ),
        );

        return $this->client->post(self::CERT_URL, $options)->getBody()->getContents();
    }

    /**
     * Add the timestamped data to existing zip archive
     */
    private function addToZip(string $zip, string $data): string
    {
        // write the zip to a temporary file
        $tmpFilePath = FsTools::getCacheFile();
        $tmpFilePathFs = FsTools::getFs(dirname($tmpFilePath));
        $tmpFilePathFs->write(basename($tmpFilePath), $zip);

        $ZipArchive = new ZipArchive();
        $res = $ZipArchive->open($tmpFilePath);
        if ($res !== true) {
            throw new FilesystemErrorException('Error opening the zip archive!');
        }
        $ZipArchive->addFromString('timestamped-data.json', $data);
        $ZipArchive->close();
        // return the path where the zip is stored in temp folder
        return $tmpFilePath;
    }
}

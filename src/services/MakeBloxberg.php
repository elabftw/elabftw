<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2015 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Controllers\DownloadController;
use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use ZipArchive;

/**
 * Send data to Bloxberg server
 * elabid is submitted as the 'author' attribute
 */
class MakeBloxberg extends AbstractMake
{
    /**
     * This pubkey is currently the same for everyone
     * Information about the user/institution is stored in the metadataJson field
     */
    private const PUB_KEY = '0xc4d84f32cd6fd05e2e292c171f5209a678525002';

    private const CERT_URL = 'https://certify.bloxberg.org/createBloxbergCertificate';

    private const PROOF_URL = 'https://certify.bloxberg.org/generatePDF';

    private const API_KEY_URL = 'https://get.elabftw.net/?bloxbergapikey';

    /** @var AbstractEntity $Entity */
    protected $Entity;

    private string $apiKey;

    public function __construct(private Client $client, AbstractEntity $entity)
    {
        parent::__construct($entity);
        $this->Entity->canOrExplode('write');
        $this->apiKey = $this->getApiKey();
    }

    public function timestamp(): bool
    {
        $pdf = $this->getPdf();
        $pdfHash = hash('sha256', $pdf);

        try {
            // first request sends the hash to the certify endpoint
            $certifyResponse = json_decode($this->certify($pdfHash));
            // now we send the previous response to another endpoint to get the pdf back in a zip archive
            $proofResponse = $this->client->post(self::PROOF_URL, array(
                'headers' => array(
                    'api_key' => $this->apiKey,
                ),
                'json' => $certifyResponse, ));
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), (int) $e->getCode(), $e);
        }

        // the binary response is a zip archive that contains the certificate in pdf format
        $zip = $proofResponse->getBody()->getContents();
        // save the zip file as an upload
        $uploadId = $this->Entity->Uploads->createFromString('zip', $this->getFileName(), $zip);
        return $this->addToZip($pdf, $uploadId);
    }

    public function getFileName(): string
    {
        $DateTime = new DateTimeImmutable();
        return sprintf('bloxberg-proof_%s', $DateTime->format('c'));
    }

    private function getApiKey(): string
    {
        $res = $this->client->get(self::API_KEY_URL);
        if ($res->getStatusCode() !== 200) {
            throw new ImproperActionException('Could not fetch api key. Please try again later.');
        }
        return (string) $res->getBody();
    }

    private function getPdf(): string
    {
        $MakePdf = new MakePdf($this->Entity);
        return $MakePdf->getPdf();
    }

    private function certify(string $hash): string
    {
        $options = array(
            'headers' => array(
                'api_key' => $this->apiKey,
            ),
            'json' => array(
                'publicKey' => self::PUB_KEY,
                'crid' => array('0x' . $hash),
                'cridType' => 'sha2-256',
                'enableIPFS' => false,
                'metadataJson' => json_encode(array(
                    'author' => $this->Entity->entityData['fullname'],
                    'elabid' => $this->Entity->entityData['elabid'],
                    'instanceid' => 'not implemented',
                )),
            ),
        );

        return $this->client->post(self::CERT_URL, $options)->getBody()->getContents();
    }

    /**
     * Add the timestamped pdf to existing zip archive
     */
    private function addToZip(string $pdf, int $uploadId): bool
    {
        // get info about the file to get the long_name
        $this->Entity->Uploads->setId($uploadId);
        $zipFile = $this->Entity->Uploads->read(new ContentParams());
        // add the timestamped pdf to the zip archive
        $ZipArchive = new ZipArchive();
        // we need this to get the path to the file
        $DownloadController = new DownloadController($zipFile['long_name']);
        $res = $ZipArchive->open($DownloadController->getFilePath());
        if ($res !== true) {
            throw new FilesystemErrorException('Error opening the zip archive!');
        }
        $ZipArchive->addFromString('timestamped-data.pdf', $pdf);
        return $ZipArchive->close();
    }
}

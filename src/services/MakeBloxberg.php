<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2015, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\CreateImmutableUpload;
use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\Config;
use Elabftw\Traits\UploadTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use function json_decode;
use function json_encode;
use ZipArchive;

/**
 * Send data to Bloxberg server
 * elabid is submitted as the 'author' attribute
 */
class MakeBloxberg extends AbstractMake
{
    use UploadTrait;

    /**
     * This pubkey is currently the same for everyone
     * Information about the user/institution is stored in the metadataJson field
     */
    private const PUB_KEY = '0xc4d84f32cd6fd05e2e292c171f5209a678525002';

    private const CERT_URL = 'https://certify.bloxberg.org/createBloxbergCertificate';

    private const PROOF_URL = 'https://certify.bloxberg.org/generatePDF';

    private const API_KEY_URL = 'https://get.elabftw.net/?bloxbergapikey';

    private string $apiKey;

    public function __construct(private Client $client, AbstractConcreteEntity $entity)
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
                'json' => $certifyResponse,
            ));
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), (int) $e->getCode(), $e);
        }

        // the binary response is a zip archive that contains the certificate in pdf format
        $zip = $proofResponse->getBody()->getContents();
        // add the pdf to the zipfile and get the path to where it is stored in cache
        $tmpFilePath = $this->addToZip($zip, $pdf);
        // save the zip file as an upload
        return (bool) $this->Entity->Uploads->create(
            new CreateImmutableUpload(
                $this->getFileName(),
                $tmpFilePath,
                sprintf(_('Timestamp archive by %s'), $this->Entity->Users->userData['fullname'])
            )
        );
    }

    public function getFileName(): string
    {
        $DateTime = new DateTimeImmutable();
        return sprintf('bloxberg-proof_%s.zip', $DateTime->format('c'));
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
        $userData = $this->Entity->Users->userData;
        $MpdfProvider = new MpdfProvider(
            $userData['fullname'],
            $userData['pdf_format'],
            (bool) $userData['pdfa'],
        );
        $MakePdf = new MakePdf($MpdfProvider, $this->Entity);
        return $MakePdf->getFileContent();
    }

    private function certify(string $hash): string
    {
        // in order to be GDPR compliant, it is possible to anonymize the author
        $author = $this->Entity->entityData['fullname'];
        $Config = Config::getConfig();
        if ($Config->configArr['blox_anon']) {
            $author = 'eLabFTW user';
        }

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
                    'author' => $author,
                    'elabid' => $this->Entity->entityData['elabid'],
                    'instanceid' => 'not implemented',
                ), JSON_THROW_ON_ERROR, 512),
            ),
        );

        return $this->client->post(self::CERT_URL, $options)->getBody()->getContents();
    }

    /**
     * Add the timestamped pdf to existing zip archive
     */
    private function addToZip(string $zip, string $pdf): string
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
        $ZipArchive->addFromString('timestamped-data.pdf', $pdf);
        $ZipArchive->close();
        // return the path where the zip is stored in temp folder
        return $tmpFilePath;
    }
}

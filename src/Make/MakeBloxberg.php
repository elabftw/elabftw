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

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\Messages;
use Elabftw\Enums\State;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use RuntimeException;
use ZipArchive;

use function json_decode;
use function json_encode;

/**
 * Send data to Bloxberg server
 * elabid is submitted as the 'author' attribute
 */
final class MakeBloxberg extends AbstractMakeTimestamp
{
    /**
     * This pubkey is currently the same for everyone
     * Information about the user/institution is stored in the metadataJson field
     */
    private const string PUB_KEY = '0xc4d84f32cd6fd05e2e292c171f5209a678525002';

    private const string CERT_URL = 'https://certify.bloxberg.org/createBloxbergCertificate';

    private const string PROOF_URL = 'https://certify.bloxberg.org/generatePDF';

    private const string API_KEY_URL = 'https://get.elabftw.net/?bloxbergapikey';

    private string $apiKey;

    public function __construct(protected Users $requester, protected AbstractEntity $entity, protected array $configArr, private HttpGetter $getter)
    {
        parent::__construct($requester, $entity, $configArr, ExportFormat::Json);
        if ($configArr['blox_enabled'] !== '1') {
            throw new ImproperActionException('Bloxberg timestamping is disabled on this instance.');
        }
        $this->apiKey = $getter->get(self::API_KEY_URL);
    }

    public function timestamp(): int
    {
        $data = $this->generateData();
        $dataHash = hash('sha256', $data);

        // first request sends the hash to the certify endpoint
        $certifyResponse = json_decode($this->certify($dataHash), true);
        if ($certifyResponse === null) {
            throw new ImproperActionException(Messages::GenericError->toHuman());
        }
        if (isset($certifyResponse['errors'])) {
            throw new ImproperActionException(implode(', ', $certifyResponse['errors']));
        }
        // now we send the previous response to another endpoint to get the pdf back in a zip archive
        // the binary response is a zip archive that contains the certificate in pdf format
        $zip = $this->getter->postJson(self::PROOF_URL, $certifyResponse, array('api-key' => $this->apiKey));

        // add the data to the zipfile and get the path to where it is stored in cache
        $tmpFilePath = $this->addToZip($zip, $data);
        // update timestamp on the entry
        $this->updateTimestamp(date('Y-m-d H:i:s'));
        // save the zip file as an upload
        return $this->entity->Uploads->create(
            new CreateUploadFromLocalFile(
                $this->getFileName(),
                $tmpFilePath,
                sprintf(_('Timestamp archive by %s'), $this->entity->Users->userData['fullname']),
                immutable: 1,
                state: State::Archived,
            ),
            isTimestamp: true,
        );
    }

    private function certify(string $hash): string
    {
        // in order to be GDPR compliant, it is possible to anonymize the author
        $author = $this->entity->entityData['fullname'];
        if ($this->configArr['blox_anon']) {
            $author = 'eLabFTW user';
        }

        $json = array(
            'publicKey' => self::PUB_KEY,
            'crid' => array('0x' . $hash),
            'cridType' => 'sha2-256',
            'enableIPFS' => false,
            'metadataJson' => json_encode(array(
                'author' => $author,
                'elabid' => $this->entity->entityData['elabid'],
                'instanceid' => 'not implemented',
            ), JSON_THROW_ON_ERROR, 4),
        );

        return $this->getter->postJson(self::CERT_URL, $json, array('api-key' => $this->apiKey));
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
            throw new RuntimeException('Error opening the zip archive!');
        }
        $ZipArchive->addFromString('timestamped-data.json', $data);
        $ZipArchive->close();
        // return the path where the zip is stored in temp folder
        return $tmpFilePath;
    }
}

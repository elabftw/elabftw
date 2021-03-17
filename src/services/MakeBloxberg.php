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

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use Elabftw\Models\AbstractEntity;
use Elabftw\Exceptions\ImproperActionException;

/**
 * Send data to Bloxberg server
 * elabid is submitted as the 'author' attribute
 */
class MakeBloxberg extends AbstractMake
{
    private AbstractEntity $Entity;

    private const BLOXBERG_CERT_URL = 'https://certify.bloxberg.org/certifyData';

    private const BLOXBERG_PROOF_URL = 'https://certify.bloxberg.org/generateCertificate';

    public function __construct(AbstractEntity $entity)
    {
        parent::__construct($entity);
        $this->Entity->canOrExplode('write');
    }

    public function timestamp()
    {
        $client = new Client();
        $options = array(
            'json' => array(
                'certifyVariables' => array(
                    'checksum' => $checksum,
                    'authorName' => $this->Entity->entityData['elabid'],
                    'timestampString' => (string) time(),
                )
            )
        );

        try {
            $response = $client->post(self::BLOXBERG_URL, $options);
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), (int) $e->getCode(), $e);
        }

        $content = json_decode($response->getBody()->getContents());
        $transactionHash = $content->txReceipt->events->createDataEvent->transactionHash;

        // now call the Bloxberg API to get the pdf proof, this comes back as binary data
        $retrieveOptions = array(
            'json' => array(
                'certificateVariables' => array(
                    'transactionHash' => (string) $transaction_hash,
                )
            )
        );

        try {
            $proofResponse = $client->post(self::BLOXBERG_PROOF_URL, $retrieveOptions);
        } catch (RequestException $e) {
            throw new ImproperActionException($e->getMessage(), (int) $e->getCode(), $e);
        }

        $pdf = $proofResponse->getBody()->getContents();
        var_dump($pdf);die;
        $this->saveProof($pdf);
    }

    private function saveProof(string $pdf)
    {
        $longName = $this->getLongName() . '-bloxberg-proof.pdf';
        $filePath = $this->getUploadsPath() . $longName;
        $dir = dirname($filePath);
        if (!is_dir($dir) && !\mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new FilesystemErrorException('Cannot create folder! Check permissions of uploads folder.');
        }
        if (!file_put_contents($filePath, $pdf_raw)) {
            throw new FilesystemErrorException('Cannot save token to disk!');
        }
        $this->bloxbergStamp = 1;
        $this->bloxbergproofPath = $filePath;

        $realName = $this->getElabid() . '_bloxberg.pdf';
        $hash = $this->getHash($this->bloxbergproofPath);

        // keep a trace of where we put the file
        $sql = 'INSERT INTO uploads(real_name, long_name, comment, item_id, userid, type, hash, hash_algorithm)
            VALUES(:real_name, :long_name, :comment, :item_id, :userid, :type, :hash, :hash_algorithm)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        $req->bindValue(':comment', 'Bloxberg timestamp proof');
        $req->bindParam(':item_id', $this->Entity->id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Entity->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindValue(':type', 'bloxberg-proof'); // save the proof in 'experiements' category to make sure it is zip exported, this could be solved more elegantly...
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->stampParams['hash']);
        if (!$req->execute()) {
            throw new ImproperActionException('Cannot insert into SQL!');
        }
    }



}

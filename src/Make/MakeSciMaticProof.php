<?php

/**
 * @author Said Nadeem / ZincirX Inc.
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use Elabftw\Enums\ExportFormat;
use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Users\Users;
use Elabftw\Params\Guard;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\SciMaticProofClient;
use ZipArchive;

use function date;
use function hash;
use function implode;
use function json_encode;
use function sprintf;
use function str_replace;

/**
 * Timestamp with SciMatic Proof (https://proof.scimatic.net/spec/receipt-v1)
 * This is not RFC 3161: the SHA-256 hash of the data is sent and a JSON receipt signed with ML-DSA-44 comes back.
 * Receipts are later batched in a Merkle tree whose root is recorded on the SciChain blockchain.
 * The API key is stored in ts_password.
 */
final class MakeSciMaticProof extends AbstractMakeTimestamp
{
    private SciMaticProofClient $client;

    public function __construct(
        protected Users $requester,
        protected AbstractEntity $entity,
        protected array $configArr,
        protected ExportFormat $dataFormat,
        HttpGetter $getter,
    ) {
        parent::__construct($requester, $entity, $configArr, $dataFormat);
        $this->client = new SciMaticProofClient($getter, Guard::getNonEmptyStringValueOfRequiredParam('ts_password', $configArr));
    }

    /**
     * Get a receipt for the hash of the data, and save the data with the receipt in a zip archive
     */
    public function timestamp(CreateUploadParamsInterface $create): int
    {
        $data = $this->generateData();
        $receipt = $this->client->createReceipt(hash('sha256', $data));

        // e.g. 20220210171842-timestamped.zip
        $zipName = $create->getFileName();
        // e.g. 20220210171842-timestamped.(json|pdf), like the RFC 3161 archives
        $dataName = str_replace('zip', $this->dataFormat->value, $zipName);
        $receiptName = str_replace('.zip', '.receipt.json', $zipName);

        $ZipArchive = new ZipArchive();
        $ZipArchive->open($create->getFilePath(), ZipArchive::CREATE);
        $ZipArchive->addFromString($dataName, $data);
        $ZipArchive->addFromString($receiptName, json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $ZipArchive->addFromString('README.txt', $this->getReadme($receipt['body'], $dataName, $receiptName));
        $ZipArchive->close();

        // use the time recorded by SciMatic, in the server timezone like the RFC 3161 tokens
        $this->updateTimestamp(date('Y-m-d H:i:s', new DateTimeImmutable($receipt['body']['recorded_at'])->getTimestamp()));
        return $this->entity->Uploads->create($create, isTimestamp: true);
    }

    /**
     * Explain what the archive holds and how to check it, with a link to the public receipt page
     */
    private function getReadme(array $body, string $dataName, string $receiptName): string
    {
        $pageUrl = SciMaticProofClient::getReceiptPageUrl($body['id']);
        $lines = array(
            sprintf('SciMatic Proof receipt %s', $body['id']),
            '',
            sprintf('Data: %s', $dataName),
            sprintf('SHA-256 of the data: %s', $body['hash']['value']),
            sprintf('Receipt: %s (as issued, before anchoring)', $receiptName),
            sprintf('Recorded at: %s (UTC)', $body['recorded_at']),
            sprintf('Receipt page: %s', $pageUrl),
            sprintf('Receipt with its anchor: %s/receipt.json', $pageUrl),
            '',
            'Only the SHA-256 hash of the data was sent to SciMatic.',
            'To check the receipt offline, follow https://proof.scimatic.net/spec/receipt-v1',
            'with the published keys and the verifier from https://proof.scimatic.net/tools/verify-receipt.mjs',
            'This is not an RFC 3161 timestamp and not an eIDAS-qualified timestamp.',
        );
        if ($body['env'] === 'test') {
            $lines[] = 'TEST receipt (sandbox key): it is never anchored and must not be used as evidence.';
        }
        return implode("\n", $lines) . "\n";
    }
}

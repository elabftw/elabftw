<?php

/**
 * @author Said Nadeem / ZincirX Inc.
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\BuildInfo;
use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\Exception\RequestException;
use SensitiveParameter;

use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function sprintf;

/**
 * Client for the SciMatic Proof API
 * Only a SHA-256 hash is sent. The answer is a receipt (body, digest, sig, anchor) signed with ML-DSA-44,
 * which can be checked offline: https://proof.scimatic.net/spec/receipt-v1
 * Keys starting with prf_test_ give sandbox receipts (env "test"): never anchored and never charged.
 */
final class SciMaticProofClient
{
    public const string API_URL = 'https://api.scimatic.net/v1/receipts';

    public const string RECEIPT_PAGE_URL = 'https://proof.scimatic.net/r/%s';

    // receipt ids are ULIDs, see section 4 of the specification
    private const string ID_REGEX = '/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/';

    private const string RECORDED_AT_REGEX = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/';

    public function __construct(private HttpGetter $getter, #[SensitiveParameter] private string $apiKey) {}

    /**
     * Send the hash and get back the receipt issued for it
     *
     * @param string $hash SHA-256 as 64 lowercase hexadecimal characters
     */
    public function createReceipt(string $hash): array
    {
        if (preg_match('/^[0-9a-f]{64}$/', $hash) !== 1) {
            throw new ImproperActionException('SciMatic Proof: only a SHA-256 hash can be sent.');
        }
        try {
            $response = $this->getter->post(self::API_URL, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'User-Agent' => 'Elabftw/' . BuildInfo::VERSION,
                ),
                'json' => array('hash' => $hash, 'alg' => 'sha-256'),
            ));
        } catch (RequestException $e) {
            // keep the previous exception: a ServerException lets the timestamping command retry
            throw new ImproperActionException($this->getErrorMessage($e), $e->getCode(), $e);
        }
        $receipt = $this->decode((string) $response->getBody())['receipt'] ?? null;
        if (!is_array($receipt) || !$this->isReceiptFor($receipt, $hash)) {
            throw new ImproperActionException('SciMatic Proof: the answer does not contain a receipt for the hash that was sent.');
        }
        return $receipt;
    }

    public static function getReceiptPageUrl(string $id): string
    {
        return sprintf(self::RECEIPT_PAGE_URL, $id);
    }

    /**
     * Check the fields we rely on: the full check (signature, Merkle path) is done offline with the published keys
     */
    private function isReceiptFor(array $receipt, string $hash): bool
    {
        $body = $receipt['body'] ?? null;
        return is_array($body)
            && ($body['hash']['alg'] ?? null) === 'sha-256'
            && ($body['hash']['value'] ?? null) === $hash
            && in_array($body['env'] ?? null, array('live', 'test'), true)
            && is_string($body['id'] ?? null) && preg_match(self::ID_REGEX, $body['id']) === 1
            && is_string($body['recorded_at'] ?? null) && preg_match(self::RECORDED_AT_REGEX, $body['recorded_at']) === 1
            && is_string($receipt['digest'] ?? null)
            && is_string($receipt['sig'] ?? null);
    }

    /**
     * Errors look like {"error": {"code": "insufficient_credits", "message": "..."}}
     */
    private function getErrorMessage(RequestException $e): string
    {
        $response = $e->getResponse();
        if ($response === null) {
            return sprintf('SciMatic Proof: %s', $e->getMessage());
        }
        $error = $this->decode((string) $response->getBody())['error'] ?? null;
        if (is_array($error) && is_string($error['code'] ?? null) && is_string($error['message'] ?? null)) {
            return sprintf('SciMatic Proof (%d %s): %s', $response->getStatusCode(), $error['code'], $error['message']);
        }
        return sprintf('SciMatic Proof: unexpected answer (HTTP %d).', $response->getStatusCode());
    }

    private function decode(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : array();
    }
}

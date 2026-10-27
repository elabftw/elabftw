<?php

declare(strict_types=1);
/**
 * @author Said Nadeem / ZincirX Inc.
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Throwable;

use function array_keys;
use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function sprintf;
use function str_repeat;

class SciMaticProofClientTest extends \PHPUnit\Framework\TestCase
{
    // SHA-256 of the data of test vector 1 in the receipt specification
    private const string HASH = '01db50ca303870137c61f537562b894aeab390d92bb3af263cc8646f07461715';

    private const string API_KEY = 'prf_test_not_a_real_key';

    private MockHandler $mock;

    private string $successResponse;

    protected function setUp(): void
    {
        // answer of POST /v1/receipts for test vector 1: sandbox key, so env is "test" and anchor is null
        $this->successResponse = (string) file_get_contents(dirname(__DIR__, 2) . '/_data/scimatic-proof-receipt-response.json');
    }

    public function testCreateReceipt(): void
    {
        $receipt = $this->getClient(new Response(201, array(), $this->successResponse))->createReceipt(self::HASH);
        $this->assertSame(array('body', 'digest', 'sig', 'anchor'), array_keys($receipt));
        $this->assertSame(self::HASH, $receipt['body']['hash']['value']);
        $this->assertSame('test', $receipt['body']['env']);
        $this->assertNull($receipt['anchor']);
    }

    public function testOnlyTheHashIsSent(): void
    {
        $this->getClient(new Response(201, array(), $this->successResponse))->createReceipt(self::HASH);
        $request = $this->mock->getLastRequest();
        $this->assertNotNull($request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(SciMaticProofClient::API_URL, (string) $request->getUri());
        $this->assertSame('Bearer ' . self::API_KEY, $request->getHeaderLine('Authorization'));
        $this->assertSame(array('hash' => self::HASH, 'alg' => 'sha-256'), json_decode((string) $request->getBody(), true));
    }

    public function testRefuseToSendSomethingElse(): void
    {
        $client = $this->getClient(new Response(201, array(), $this->successResponse));
        $this->expectException(ImproperActionException::class);
        try {
            $client->createReceipt('some experiment data');
        } finally {
            // nothing left the instance
            $this->assertNull($this->mock->getLastRequest());
        }
    }

    public function testInvalidApiKey(): void
    {
        $this->assertApiError(401, 'invalid_api_key', 'Missing, malformed or revoked API key.', ClientException::class);
    }

    public function testNoReceiptsLeft(): void
    {
        $this->assertApiError(402, 'insufficient_credits', "This month's free live receipts are used and no paid credits are left.", ClientException::class);
    }

    public function testDailyCapReached(): void
    {
        $this->assertApiError(429, 'daily_cap_reached', "This account's daily limit of 1000 live receipts is reached.", ClientException::class);
    }

    public function testServiceUnavailable(): void
    {
        // a ServerException as previous exception lets the experiments:timestamp command retry
        $this->assertApiError(503, 'signing_unavailable', 'Receipts cannot be issued right now; retry shortly.', ServerException::class);
    }

    public function testUnexpectedErrorBody(): void
    {
        $client = $this->getClient(new Response(502, array(), '<html>Bad Gateway</html>'));
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('SciMatic Proof: unexpected answer (HTTP 502).');
        $client->createReceipt(self::HASH);
    }

    public function testConnectionError(): void
    {
        $client = $this->getClient(new ConnectException('Connection refused', new Request('POST', SciMaticProofClient::API_URL)));
        $this->expectException(ImproperActionException::class);
        $client->createReceipt(self::HASH);
    }

    public function testReceiptForAnotherHash(): void
    {
        $client = $this->getClient(new Response(201, array(), $this->successResponse));
        $this->expectException(ImproperActionException::class);
        $client->createReceipt(str_repeat('a', 64));
    }

    public function testNotAReceipt(): void
    {
        $client = $this->getClient(new Response(201, array(), '{"object": "receipt"}'));
        $this->expectException(ImproperActionException::class);
        $client->createReceipt(self::HASH);
    }

    public function testGetReceiptPageUrl(): void
    {
        $this->assertSame('https://proof.scimatic.net/r/01M51Z12M0BE0FCQ2C68VYKQRN', SciMaticProofClient::getReceiptPageUrl('01M51Z12M0BE0FCQ2C68VYKQRN'));
    }

    /**
     * @param class-string<Throwable> $previousClass
     */
    private function assertApiError(int $status, string $code, string $message, string $previousClass): void
    {
        $body = json_encode(array('error' => array('code' => $code, 'message' => $message)), JSON_THROW_ON_ERROR);
        $client = $this->getClient(new Response($status, array('Content-Type' => 'application/json'), $body));
        try {
            $client->createReceipt(self::HASH);
            $this->fail('No exception thrown');
        } catch (ImproperActionException $e) {
            $this->assertSame(sprintf('SciMatic Proof (%d %s): %s', $status, $code, $message), $e->getMessage());
            $this->assertInstanceOf($previousClass, $e->getPrevious());
        }
    }

    private function getClient(Response|ConnectException $response): SciMaticProofClient
    {
        // don't use the real guzzle client, but use a mock that keeps the last request
        // https://docs.guzzlephp.org/en/stable/testing.html
        $this->mock = new MockHandler(array($response));
        $client = new Client(array('handler' => HandlerStack::create($this->mock)));
        return new SciMaticProofClient(new HttpGetter($client), self::API_KEY);
    }
}

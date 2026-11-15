<?php

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\TimestampResponse;
use Elabftw\Enums\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

use function dirname;

class TimestampUtilsVerifyTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyCertVerifies(): void
    {
        $this->assertInstanceOf(TimestampResponse::class, $this->timestampWithCert(''));
    }

    public function testNullCertVerifies(): void
    {
        $this->assertInstanceOf(TimestampResponse::class, $this->timestampWithCert(null));
    }

    public function testWithCertStillVerifies(): void
    {
        $this->assertInstanceOf(TimestampResponse::class, $this->timestampWithCert(dirname(__DIR__, 2) . '/_data/ts-fake-tsa.pem'));
    }

    private function timestampWithCert(?string $cert): TimestampResponse
    {
        // Fixtures use a fake self-signed CA and TSA; the response was generated with openssl ts -reply.
        $fixturesFs = Storage::FIXTURES->getStorage()->getFs();
        $mock = new MockHandler(array(
            new Response(200, array(), $fixturesFs->read('ts-fake-response.tsr')),
        ));
        $client = new Client(array('handler' => HandlerStack::create($mock)));
        $config = array(
            'ts_login' => '',
            'ts_password' => '',
            'ts_url' => 'https://tsa.invalid',
            'ts_hash' => 'sha256',
            'ts_cert' => $cert,
            'ts_chain' => dirname(__DIR__, 2) . '/_data/ts-fake-ca.pem',
        );
        $utils = new TimestampUtils($client, $fixturesFs->read('ts-fake-data.txt'), $config, new TimestampResponse());
        return $utils->timestamp();
    }
}

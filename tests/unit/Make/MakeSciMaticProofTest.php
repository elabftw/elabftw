<?php

declare(strict_types=1);
/**
 * @author Said Nadeem / ZincirX Inc.
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Elabftw\CreateUploadFromLocalFile;
use Elabftw\Elabftw\FsTools;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\State;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

use function array_keys;
use function json_decode;
use function json_encode;

class MakeSciMaticProofTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private array $configArr = array(
        'proxy' => '',
        'ts_limit' => '0',
        'ts_password' => 'prf_test_not_a_real_key',
    );

    private MockHandler $mock;

    public function testGetFileName(): void
    {
        $Maker = new MakeSciMaticProof(new Users(1, 1), $this->getFreshExperiment(), $this->configArr, ExportFormat::Json, $this->getGetter());
        $this->assertStringContainsString('-timestamped.zip', $Maker->getFileName());
    }

    public function testTimestamp(): void
    {
        $Experiment = $this->getFreshExperiment();
        $Maker = new MakeSciMaticProof(new Users(1, 1), $Experiment, $this->configArr, ExportFormat::Json, $this->getGetter());
        $create = new CreateUploadFromLocalFile($Maker->getFileName(), FsTools::getCacheFile() . '.zip', 'Timestamp archive by unit test', 1, State::Archived);
        $this->assertIsInt($Maker->timestamp($create));
        // only the hash of the data left the instance
        $request = $this->mock->getLastRequest();
        $this->assertNotNull($request);
        $sent = (string) $request->getBody();
        $this->assertSame(array('hash', 'alg'), array_keys(json_decode($sent, true)));
        $this->assertStringNotContainsString($Experiment->readOne()['title'], $sent);
        $this->assertSame(1, $Experiment->readOne()['timestamped']);
    }

    public function testMissingApiKey(): void
    {
        $configArr = $this->configArr;
        $configArr['ts_password'] = '';
        $this->expectException(ImproperActionException::class);
        new MakeSciMaticProof(new Users(1, 1), $this->getFreshExperiment(), $configArr, ExportFormat::Json, $this->getGetter());
    }

    public function testNoReceiptsLeft(): void
    {
        $body = json_encode(array('error' => array('code' => 'insufficient_credits', 'message' => 'No receipts left.')), JSON_THROW_ON_ERROR);
        $Maker = new MakeSciMaticProof(new Users(1, 1), $this->getFreshExperiment(), $this->configArr, ExportFormat::Json, $this->getGetter(new Response(402, array(), $body)));
        $this->expectException(ImproperActionException::class);
        $Maker->timestamp(new CreateUploadFromLocalFile($Maker->getFileName(), FsTools::getCacheFile() . '.zip', 'Timestamp archive by unit test', 1, State::Archived));
    }

    private function getGetter(?Response $response = null): HttpGetter
    {
        $fixture = json_decode(Storage::FIXTURES->getStorage()->getFs()->read('scimatic-proof-receipt-response.json'), true);
        // by default answer like the API: with a receipt for the hash that was sent
        $handler = $response ?? function (RequestInterface $request) use ($fixture): Response {
            $hash = json_decode((string) $request->getBody(), true)['hash'];
            $fixture['hash']['value'] = $hash;
            $fixture['receipt']['body']['hash']['value'] = $hash;
            return new Response(201, array(), json_encode($fixture, JSON_THROW_ON_ERROR));
        };
        $this->mock = new MockHandler(array($handler));
        return new HttpGetter(new Client(array('handler' => HandlerStack::create($this->mock))));
    }
}

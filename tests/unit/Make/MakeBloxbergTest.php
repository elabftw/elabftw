<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Enums\Storage;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Filesystem;
use RuntimeException;

class MakeBloxbergTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private MakeBloxberg $Make;

    private Filesystem $fixturesFs;

    private Users $requester;

    private HttpGetter $httpGetter;

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        // taken from the example response on the api doc
        // https://app.swaggerhub.com/apis/bloxberg/fast-api/0.1.0#/certificate/createBloxbergCertificate_createBloxbergCertificate_post
        $this->fixturesFs = Storage::FIXTURES->getStorage()->getFs();
        $successResponseCertify = $this->fixturesFs->read('bloxberg-cert-response.json');
        // a small zip that will act as what we receive from the server
        $zip = $this->fixturesFs->read('example.zip');
        // don't use the real guzzle client, but use a mock
        // https://docs.guzzlephp.org/en/stable/testing.html
        $mock = new MockHandler(array(
            new Response(200, array(), 'a-fake-api-key'),
            new Response(200, array(), $successResponseCertify),
            new Response(200, array(), $zip),
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $configArr = Config::getConfig()->configArr;
        $configArr['blox_anon'] = '1';
        $this->httpGetter = new HttpGetter($client);
        $this->Make = new MakeBloxberg($this->requester, $this->getFreshExperiment(), $configArr, $this->httpGetter);
    }

    public function testGetFileName(): void
    {
        $this->assertStringContainsString('-timestamped.zip', $this->Make->getFileName());
    }

    public function testTimestamp(): void
    {
        $this->assertIsInt($this->Make->timestamp());
    }

    public function testTimestampDisallowed(): void
    {
        $configArr = array('ts_limit' => '666', 'blox_enabled' => '0');
        $this->expectException(ImproperActionException::class);
        $this->Make = new MakeBloxberg($this->requester, $this->getFreshExperiment(), $configArr, $this->httpGetter);
    }

    public function testTimestampFailGettingKey(): void
    {
        $mock = new MockHandler(array(
            new ConnectException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $this->expectException(ImproperActionException::class);
        $getter = new HttpGetter($client);
        new MakeBloxberg($this->requester, $this->getFreshExperiment(), Config::getConfig()->configArr, $getter);
    }

    public function testTimestampFail(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), 'a-fake-api-key'),
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $getter = new HttpGetter($client);
        $Make = new MakeBloxberg($this->requester, $this->getFreshExperiment(), Config::getConfig()->configArr, $getter);
        $this->expectException(RequestException::class);
        $Make->timestamp();
    }

    public function testTimestampZipFail(): void
    {
        $successResponseCertify = $this->fixturesFs->read('bloxberg-cert-response.json');
        $mock = new MockHandler(array(
            new Response(200, array(), 'a-fake-api-key'),
            new Response(200, array(), $successResponseCertify),
            new Response(200, array(), 'not a zip'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $getter = new HttpGetter($client);
        $Make = new MakeBloxberg($this->requester, $this->getFreshExperiment(), Config::getConfig()->configArr, $getter);
        $this->expectException(RuntimeException::class);
        $Make->timestamp();
    }
}

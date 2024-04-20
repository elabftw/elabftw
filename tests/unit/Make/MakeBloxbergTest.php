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
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Filesystem;

class MakeBloxbergTest extends \PHPUnit\Framework\TestCase
{
    private MakeBloxberg $Make;

    private Filesystem $fixturesFs;

    protected function setUp(): void
    {
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
        $entity = new Experiments(new Users(1, 1), 1);
        $configArr = Config::getConfig()->configArr;
        $configArr['blox_anon'] = '1';
        $this->Make = new MakeBloxberg($configArr, $entity, $client);
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
        $entity = new Experiments(new Users(1, 1), 1);
        $this->expectException(ImproperActionException::class);
        new MakeBloxberg(array('ts_limit' => '666', 'blox_enabled' => '0'), $entity, new Client());
    }

    public function testTimestampFailGettingKey(): void
    {
        $mock = new MockHandler(array(
            new ConnectException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $entity = new Experiments(new Users(1, 1), 1);
        $this->expectException(ImproperActionException::class);
        new MakeBloxberg(Config::getConfig()->configArr, $entity, $client);
    }

    public function testTimestampFail(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), 'a-fake-api-key'),
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $entity = new Experiments(new Users(1, 1), 1);
        $Make = new MakeBloxberg(Config::getConfig()->configArr, $entity, $client);
        $this->expectException(ImproperActionException::class);
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
        $entity = new Experiments(new Users(1, 1), 1);
        $Make = new MakeBloxberg(Config::getConfig()->configArr, $entity, $client);
        $this->expectException(FilesystemErrorException::class);
        $Make->timestamp();
    }
}

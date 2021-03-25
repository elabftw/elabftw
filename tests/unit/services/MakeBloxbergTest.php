<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users;
use function file_get_contents;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class MakeBloxbergTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        // taken from the example response on the api doc
        // https://app.swaggerhub.com/apis/bloxberg/fast-api/0.1.0#/certificate/createBloxbergCertificate_createBloxbergCertificate_post
        $successResponseCertify = file_get_contents(dirname(__DIR__, 2) . '/_data/bloxberg-cert-response.json');
        // a small zip that will act as what we receive from the server
        $zip = file_get_contents(dirname(__DIR__, 2) . '/_data/example.zip');
        // don't use the real guzzle client, but use a mock
        $mock = new MockHandler(array(
            new Response(200, array(), $successResponseCertify),
            new Response(200, array(), $zip),
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $this->Make = new MakeBloxberg($client, new Experiments(new Users(1, 1), 1));
    }

    public function testGetFileName()
    {
        $this->assertStringContainsString('bloxberg-proof_', $this->Make->getFileName());
    }

    public function testTimestamp()
    {
        $this->assertTrue($this->Make->timestamp());
    }

    public function testTimestampFail()
    {
        $mock = new MockHandler(array(
            new RequestException('Server is down?', new Request('GET', 'test')),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $Make = new MakeBloxberg($client, new Experiments(new Users(1, 1), 1));
        $this->expectException(ImproperActionException::class);
        $Make->timestamp();
    }

    public function testTimestampZipFail()
    {
        $mock = new MockHandler(array(
            new Response(200, array(), $successResponseCertify),
            new Response(200, array(), 'not a zip'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $Make = new MakeBloxberg($client, new Experiments(new Users(1, 1), 1));
        $this->expectException(FilesystemErrorException::class);
        $Make->timestamp();
    }
}

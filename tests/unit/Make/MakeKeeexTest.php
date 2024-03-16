<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class MakeKeeexTest extends \PHPUnit\Framework\TestCase
{
    public function testFromString(): void
    {
        // don't use the real guzzle client, but use a mock
        // https://docs.guzzlephp.org/en/stable/testing.html
        $mock = new MockHandler(array(
            new Response(200, array(), 'fake keeexed content'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $Maker = new MakeKeeex($client);
        $this->assertIsString($Maker->fromString('source content'));
    }

    public function testClientError(): void
    {
        // don't use the real guzzle client, but use a mock
        // https://docs.guzzlephp.org/en/stable/testing.html
        $mock = new MockHandler(array(
            new Response(400, array(), 'fake keeexed content'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $Maker = new MakeKeeex($client);
        $this->expectException(ImproperActionException::class);
        $Maker->fromString('trigger client error');
    }

    public function testServerError(): void
    {
        // don't use the real guzzle client, but use a mock
        // https://docs.guzzlephp.org/en/stable/testing.html
        $mock = new MockHandler(array(
            new Response(500, array(), 'fake keeexed content'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $Maker = new MakeKeeex($client);
        $this->expectException(ImproperActionException::class);
        $Maker->fromString('trigger server error');
    }
}

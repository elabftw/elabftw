<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Exceptions\ImproperActionException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class FingerprinterTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyUrl(): void
    {
        $this->expectException(ImproperActionException::class);
        new Fingerprinter(new HttpGetter(new Client()), '');
    }

    public function testCalculate(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), '{}'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $fp = new Fingerprinter($httpGetter, 'http://mocked.fr');
        $this->assertIsArray($fp->calculate('smi', 'C'));
    }

    public function testCalculateWentWrong(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), 'NOT JSON'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $httpGetter = new HttpGetter($client);
        $fp = new Fingerprinter($httpGetter, 'http://mocked.fr');
        $this->expectException(ImproperActionException::class);
        $fp->calculate('smi', 'C');
    }
}

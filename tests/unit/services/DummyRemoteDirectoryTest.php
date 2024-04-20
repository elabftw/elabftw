<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class DummyRemoteDirectoryTest extends \PHPUnit\Framework\TestCase
{
    public function testSearch(): void
    {
        // client is not actually used by dummy
        $mock = new MockHandler(array(
            new Response(200, array(), 'osef'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));

        $config = '[]';
        $RemoteDir = new DummyRemoteDirectory($client, $config);
        $res = $RemoteDir->search('yep');

        $this->assertEquals('Émilie', $res[0]['firstname']);
        $this->assertEquals('du Châtelet', $res[0]['lastname']);
        $this->assertEquals('emilie@example.net', $res[0]['email']);
        $this->assertFalse($res[0]['disabled']);
    }
}

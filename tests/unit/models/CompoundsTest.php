<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CompoundsTest extends \PHPUnit\Framework\TestCase
{
    private Compounds $Compounds;

    private HttpGetter $httpGetter;

    protected function setUp(): void
    {
        $mock = new MockHandler(array(
            new Response(200, array(), 'nothing'),
        ));
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(array('handler' => $handlerStack));
        $this->httpGetter = new HttpGetter($client);
        $this->Compounds = new Compounds($this->httpGetter, new Users(1, 1));
    }

    public function testCreateSearchAndDestroy(): void
    {
        $compoundId = $this->Compounds->create(
            casNumber: '438-38-7',
            pubchemCid: 3345,
            smiles: 'CCC(=O)N(C1CCN(CC1)CCC2=CC=CC=C2)C3=CC=CC=C3',
            withFingerprint: false,
        );
        $this->assertIsInt($compoundId);
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/compounds/', $this->Compounds->getApiPath());
    }
}

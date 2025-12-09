<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha <Deltablot>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Models;

use GuzzleHttp\Handler\MockHandler;
use Elabftw\Enums\Action;
use Elabftw\Enums\DspaceAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use Elabftw\Services\HttpGetter;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\InputBag;

class DspaceTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $requester;

    private Client $client;

    private HttpGetter $httpGetter;

    private Dspace $dspace;

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        $this->initDspace(new Client());
    }

    public function testGetApiPath(): void
    {
        $this->assertSame('api/v2/dspace', $this->dspace->getApiPath());
    }

    public function testReadAllDefaultsToListCollections(): void
    {
        $collectionsData = array(
            '_embedded' => array(
                'collections' => array(
                    array('uuid' => 'abc-123', 'name' => 'Collection One'),
                    array('uuid' => 'def-456', 'name' => 'Collection Two'),
                ),
            ),
            'page' => array('totalPages' => 1),
        );
        $this->setMockResponses(array(
            new Response(200, array(), json_encode($collectionsData) ?: '{}'),
        ));

        $result = $this->dspace->readAll();
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('abc-123', $result[0]['uuid']);
        $this->assertEquals('Collection One', $result[0]['name']);
    }

    public function testReadAllWithListTypesAction(): void
    {
        $typesData = array(
            '_embedded' => array(
                'entries' => array(
                    array('value' => 'article', 'display' => 'Article'),
                    array('value' => 'book', 'display' => 'Book'),
                ),
            ),
        );
        $this->setMockResponses(array(
            new Response(200, array(), json_encode($typesData) ?: '{}'),
        ));

        $queryParams = new InputBag(array('dspace_action' => DspaceAction::ListTypes->value));
        $q = $this->dspace->getQueryParams($queryParams);
        $result = $this->dspace->readAll($q);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('_embedded', $result);
        $this->assertCount(2, $result['_embedded']['entries']);
    }

    public function testPostActionThrowsWhenMissingRequiredFields(): void
    {
        $this->setMockResponses([]);
        $this->expectException(ImproperActionException::class);
        $this->dspace->postAction(Action::Create, []);
    }


    // create a mocked Dspace instance with custom HTTP responses
    private function setMockResponses(array $responses): void
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $this->initDspace(new Client(['handler' => $handlerStack]));
    }

    private function initDspace(Client $client): void
    {
        $this->client = $client;
        $this->httpGetter = new HttpGetter($this->client);
        $this->dspace = new Dspace($this->requester, $this->httpGetter, 'https://dspace.example.org/', 'user', 'encPasswordDummy');
    }
}

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

use Elabftw\Enums\EntityType;
use GuzzleHttp\Handler\MockHandler;
use Elabftw\Enums\Action;
use Elabftw\Enums\DSpaceAction;
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

    private Dspace $dspace;

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        $this->initDspace(new Client());
    }

    public function testGetApiPath(): void
    {
        $this->assertSame('api/v2/dspace/', $this->dspace->getApiPath());
    }

    public function testReadAllWithGetCollections(): void
    {
        $result = $this->runReadAllTest(DSpaceAction::GetCollections, array(
            '_embedded' => array(
                'collections' => array(
                    array('uuid' => 'abc-123', 'name' => 'Collection One'),
                    array('uuid' => 'def-456', 'name' => 'Collection Two'),
                ),
            ),
            'page' => array('totalPages' => 1),
        ));
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('abc-123', $result[0]['uuid']);
        $this->assertEquals('Collection One', $result[0]['name']);
    }

    public function testReadAllWithGetTypesAction(): void
    {
        $result = $this->runReadAllTest(DSpaceAction::GetTypes, array(
            '_embedded' => array(
                'entries' => array(
                    array('value' => 'article', 'display' => 'Article'),
                    array('value' => 'book', 'display' => 'Book'),
                ),
            ),
        ));
        $this->assertIsArray($result);
        $this->assertArrayHasKey('_embedded', $result);
        $this->assertCount(2, $result['_embedded']['entries']);
    }

    public function testReadAllWithUnhandledAction(): void
    {
        $queryParams = new InputBag(array('action' => 'somerandomaction'));
        $q = $this->dspace->getQueryParams($queryParams);
        $this->expectException(ImproperActionException::class);
        $this->dspace->readAll($q);
    }

    public function testPostActionThrowsWhenMissingRequiredFields(): void
    {
        $this->setMockResponses(array());
        $this->expectException(ImproperActionException::class);
        $this->dspace->postAction(Action::Create, array());
    }

    public function testPatchCreatesAndSubmitsItem(): void
    {
        $this->setMockResponses(array(
            new Response(200, array('DSPACE-XSRF-TOKEN' => array('abc'))), // csrf token
            new Response(200, array('Authorization' => 'Bearer abc')), // login
            new Response(200, array(), json_encode(array('id' => 123)) ?: '{}'), // create workspace
            new Response(200, array(), json_encode(array('uuid' => '1234-uuid')) ?: '{}'), // get UUID
            new Response(200), // accept license
            new Response(200), // update metadata
            new Response(200), // upload file
            new Response(200), // submit to workflow
        ), 'some-password');
        $experiment = $this->getFreshExperiment();
        $params = array(
            'collection' => '1234',
            'metadata' => array(
                array('key' => 'dc.title', 'value' => 'Test Title'),
                array('key' => 'dc.date.issued', 'value' => '2025-12-09'),
            ),
            'entity' => array(
                'type' => EntityType::Experiments->value,
                'id' => $experiment->id,
            ),
        );
        $res = $this->dspace->patch(Action::Create, $params);
        $this->assertSame(123, $res['id']);
        $this->assertSame('1234-uuid', $res['uuid']);
    }

    private function runReadAllTest(DSpaceAction $action, array $responseBody): array
    {
        $this->setMockResponses(array(new Response(200, array(), json_encode($responseBody) ?: '{}')));
        $queryParams = new InputBag(array('action' => $action->value));
        $q = $this->dspace->getQueryParams($queryParams);
        return $this->dspace->readAll($q);
    }

    // create a mocked Dspace instance with custom HTTP responses
    // optional password to test patch method
    private function setMockResponses(array $responses, ?string $password = null): void
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $encPassword = $password ?? 'encPasswordDummy';
        $this->initDspace(new Client(array('handler' => $handlerStack)), $encPassword);
    }

    private function initDspace(Client $client, string $password = 'encPasswordDummy'): void
    {
        $httpGetter = new HttpGetter($client);
        $this->dspace = new Dspace($this->requester, $httpGetter, 'https://dspace.example.org/', 'user', $password);
    }
}

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

use Elabftw\Enums\Action;
use Elabftw\Enums\DSpaceAction;
use Elabftw\Enums\EntityType;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

use function json_encode;
use function count;

class DspaceTest extends TestCase
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

    /**
     * @dataProvider providePaginatedEndpoints
     */
    public function testPaginatedEndpoints(DSpaceAction $action, string $embeddedKey, array $pages, callable $assertion): void
    {
        $result = $this->runPaginatedReadAllTest($action, $embeddedKey, $pages);
        $this->assertIsArray($result);
        $assertion($this, $result);
    }

    public static function providePaginatedEndpoints(): array
    {
        return array(
            // COLLECTIONS
            'collections single page' => array(
                DSpaceAction::GetCollections, 'collections',
                array(array(
                    array('uuid' => 'abc-123', 'name' => 'Collection One'),
                    array('uuid' => 'def-456', 'name' => 'Collection Two'),
                )),
                function (TestCase $test, array $result) {
                    $test->assertCount(2, $result);
                    $test->assertSame('abc-123', $result[0]['uuid']);
                    $test->assertSame('Collection One', $result[0]['name']);
                },
            ),
            'collections multi page' => array(
                DSpaceAction::GetCollections, 'collections',
                array(
                    array(array('uuid' => 'abc-123', 'name' => 'Collection One')),
                    array(array('uuid' => 'def-456', 'name' => 'Collection Two')),
                ),
                function (TestCase $test, array $result) {
                    $test->assertCount(2, $result);
                    $test->assertSame('abc-123', $result[0]['uuid']);
                    $test->assertSame('def-456', $result[1]['uuid']);
                },
            ),
            // TYPES
            'types single page' => array(
                DSpaceAction::GetTypes, 'entries',
                array(array(
                    array('value' => 'article', 'display' => 'Article'),
                    array('value' => 'book', 'display' => 'Book'),
                )),
                function (TestCase $test, array $result) {
                    $test->assertCount(2, $result);
                    $test->assertSame('article', $result[0]['value']);
                    $test->assertSame('Article', $result[0]['display']);
                },
            ),
            'types multi page' => array(
                DSpaceAction::GetTypes, 'entries',
                array(
                    array(array('value' => 'article')),
                    array(array('value' => 'book')),
                ),
                function (TestCase $test, array $result) {
                    $test->assertCount(2, $result);
                    $test->assertSame('article', $result[0]['value']);
                    $test->assertSame('book', $result[1]['value']);
                },
            ),
        );
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
        ));
        $experiment = $this->getFreshExperiment();
        $params = array(
            'collection' => '1234',
            'metadata' => array(
                array(
                    'key' => 'dc.title',
                    'value' => 'Test Title',
                    'section' => 'publicationStep',
                ),
                array(
                    'key' => 'dc.date.issued',
                    'value' => '2025-12-09',
                    'section' => 'publicationStep',
                ),
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

    private function runPaginatedReadAllTest(DSpaceAction $action, string $embeddedKey, array $pages): array
    {
        $responses = array();
        foreach ($pages as $entries) {
            $responses[] = new Response(200, array(), json_encode(array(
                '_embedded' => array($embeddedKey => $entries),
                'page' => array('totalPages' => count($pages)),
            )) ?: '{}');
        }
        $this->setMockResponses($responses);
        $queryParams = new InputBag(array('action' => $action->value));
        $q = $this->dspace->getQueryParams($queryParams);
        return $this->dspace->readAll($q);
    }

    private function setMockResponses(array $responses): void
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $this->initDspace(new Client(array('handler' => $handlerStack)));
    }

    private function initDspace(Client $client): void
    {
        $httpGetter = new HttpGetter($client);
        $this->dspace = new Dspace($this->requester, $httpGetter, 'https://dspace.example.org/', 'user', 'password');
    }
}

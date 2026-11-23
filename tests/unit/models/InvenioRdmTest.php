<?php

declare(strict_types=1);
/**
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use ArrayAccess;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Env;
use Elabftw\Enums\Action;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\InvenioRdmAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Services\HttpGetter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\InputBag;

use function json_decode;
use function json_encode;

class InvenioRdmTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $requester;

    /** @var array<int, array>|ArrayAccess<int, array> */
    private array|ArrayAccess $history = array();

    protected function setUp(): void
    {
        $this->requester = new Users(1, 1);
        $this->requester->userData['inveniordm_token'] = Crypto::encrypt('secret-token', Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
    }

    public function testGetApiPath(): void
    {
        $this->assertSame('api/v2/inveniordm/', $this->getInvenio(array())->getApiPath());
    }

    public function testEmptyHostThrows(): void
    {
        $this->expectException(ImproperActionException::class);
        new InvenioRdm($this->requester, new HttpGetter(new Client()), '');
    }

    public function testReadAllGetCommunities(): void
    {
        $body = array('hits' => array('hits' => array(array('id' => 'c1'))));
        $invenio = $this->getInvenio(array(new Response(200, array(), json_encode($body) ?: '{}')));
        $q = $invenio->getQueryParams(new InputBag(array('action' => InvenioRdmAction::GetCommunities->value)));
        $this->assertSame($body, $invenio->readAll($q));
    }

    public function testReadAllUnknownActionThrows(): void
    {
        $invenio = $this->getInvenio(array());
        $q = $invenio->getQueryParams(new InputBag(array('action' => 'nope')));
        $this->expectException(ImproperActionException::class);
        $invenio->readAll($q);
    }

    public function testMissingTokenThrows(): void
    {
        $this->requester->userData['inveniordm_token'] = '';
        $invenio = $this->getInvenio(array());
        $q = $invenio->getQueryParams(new InputBag(array('action' => InvenioRdmAction::GetLicenses->value)));
        $this->expectException(ImproperActionException::class);
        $invenio->readAll($q);
    }

    public function testPatchWithoutEntityThrows(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->getInvenio(array())->patch(Action::Create, array());
    }

    public function testPatchCreatesDraftAndUploadsFile(): void
    {
        $invenio = $this->getInvenio(array(
            new Response(201, array(), json_encode(array('id' => 'abc-123')) ?: '{}'), // create draft
            new Response(201), // init file upload
            new Response(200), // upload content
            new Response(200), // commit
        ));
        $experiment = $this->getFreshExperiment();
        $res = $invenio->patch(Action::Create, array(
            'title' => 'Test',
            'resource_type' => 'dataset',
            'license' => 'cc-by-4.0',
            'entity' => array('type' => EntityType::Experiments->value, 'id' => $experiment->id),
        ));
        $this->assertSame('abc-123', $res['id']);
        $this->assertSame('https://invenio.example.org/uploads/abc-123', $res['draftUrl']);
    }

    public function testPatchSendsValidCreatorAndCommunityReview(): void
    {
        $invenio = $this->getInvenio(array(
            new Response(201, array(), json_encode(array('id' => 'abc-123')) ?: '{}'), // create draft
            new Response(200), // community review request
            new Response(201), // init file upload
            new Response(200), // upload content
            new Response(200), // commit
        ));
        $experiment = $this->getFreshExperiment();
        $invenio->patch(Action::Create, array(
            'title' => 'Test',
            'community' => 'comm-1',
            'entity' => array('type' => EntityType::Experiments->value, 'id' => $experiment->id),
        ));
        $draft = json_decode((string) $this->history[0]['request']->getBody(), true);
        $creator = $draft['metadata']['creators'][0]['person_or_org'];
        $this->assertNotEmpty($creator['family_name']);
        $this->assertArrayNotHasKey('parent', $draft);
        $review = $this->history[1]['request'];
        $this->assertSame('PUT', $review->getMethod());
        $this->assertStringEndsWith('records/abc-123/draft/review', (string) $review->getUri());
        $this->assertSame(array('receiver' => array('community' => 'comm-1'), 'type' => 'community-submission'), json_decode((string) $review->getBody(), true));
    }

    public function testListRequestsAskForAllHits(): void
    {
        $invenio = $this->getInvenio(array(new Response(200, array(), '{}')));
        $invenio->readAll($invenio->getQueryParams(new InputBag(array('action' => InvenioRdmAction::GetLicenses->value))));
        $this->assertStringContainsString('size=1000', $this->history[0]['request']->getUri()->getQuery());
    }

    public function testPatchWithoutRecordIdThrows(): void
    {
        $invenio = $this->getInvenio(array(new Response(201, array(), '{}')));
        $experiment = $this->getFreshExperiment();
        $this->expectException(ImproperActionException::class);
        $invenio->patch(Action::Create, array('entity' => array('type' => EntityType::Experiments->value, 'id' => $experiment->id)));
    }

    private function getInvenio(array $responses): InvenioRdm
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $client = new Client(array('handler' => $stack));
        return new InvenioRdm($this->requester, new HttpGetter($client), 'https://invenio.example.org/');
    }
}

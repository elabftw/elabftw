<?php

declare(strict_types=1);
/**
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

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
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\InputBag;

use function json_encode;

class InvenioRdmTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Users $requester;

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

    public function testPatchWithoutRecordIdThrows(): void
    {
        $invenio = $this->getInvenio(array(new Response(201, array(), '{}')));
        $experiment = $this->getFreshExperiment();
        $this->expectException(ImproperActionException::class);
        $invenio->patch(Action::Create, array('entity' => array('type' => EntityType::Experiments->value, 'id' => $experiment->id)));
    }

    private function getInvenio(array $responses): InvenioRdm
    {
        $client = new Client(array('handler' => HandlerStack::create(new MockHandler($responses))));
        return new InvenioRdm($this->requester, new HttpGetter($client), 'https://invenio.example.org/');
    }
}

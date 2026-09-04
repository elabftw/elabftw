<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Config;
use Elabftw\Models\Links\Containers2ItemsLinks;
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function basename;
use function json_decode;
use function sprintf;

class Apiv2ControllerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testCanwriteFalse(): void
    {
        $Controller = new Apiv2Controller($this->getRandomUserInTeam(1), Request::create('/api/v2/users', 'POST'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testInvalidEndpoint(): void
    {
        $Controller = new Apiv2Controller($this->getRandomUserInTeam(1), Request::create('/api/v2/supercalifragilisticexpialidocious', 'GET', server: array('AUTHORIZATION' => 'apiKey4Test')));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testGetResponse(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/info/me?limit=12&offset=1&search=wtf', 'GET'));
        $res = $Controller->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertEquals(Response::HTTP_OK, $res->getStatusCode());
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/teams/curent', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_OK, $res->getStatusCode());
    }

    public function testDeleteEntityWithContainers(): void
    {
        $Item = $this->getFreshItem();
        $StorageUnits = new StorageUnits($Item->Users, true);
        $storageId = $StorageUnits->create('Container deletion test');
        new Containers2ItemsLinks($Item, $storageId)->createWithQuantity(1.0, 'mL');

        $hasContainersController = new Apiv2Controller(
            $Item->Users,
            Request::create(sprintf('/api/v2/items/%d/containers?has_any=1', $Item->id)),
        );
        $hasContainersResponse = $hasContainersController->getResponse();
        self::assertSame(
            array('has_containers' => true, 'containers_count' => 1),
            json_decode((string) $hasContainersResponse->getContent(), true),
        );

        $Controller = new Apiv2Controller(
            $Item->Users,
            Request::create(sprintf('/api/v2/items/%d?delete_containers=1', $Item->id), Request::METHOD_DELETE),
        );
        $Controller->canWrite = true;

        $response = $Controller->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame(0, $StorageUnits->countContainers($storageId));
    }

    public function testDeleteNonEntityModel(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $StorageUnits = new StorageUnits($user, true);
        $storageId = $StorageUnits->create('API deletion test');
        $Controller = new Apiv2Controller(
            $user,
            Request::create(
                sprintf('/api/v2/storage_units/%d', $storageId),
                Request::METHOD_DELETE,
            ),
        );
        $Controller->canWrite = true;
        $response = $Controller->getResponse();
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->expectException(ResourceNotFoundException::class);
        new StorageUnits($user, true, $storageId)->readOne();
    }

    public function testAnonymousUserCannotQueryUsersEndpoint(): void
    {
        $Controller = new Apiv2Controller(
            new AnonymousUser(1),
            Request::create('/api/v2/users'),
        );

        $res = $Controller->getResponse();

        self::assertSame(Response::HTTP_OK, $res->getStatusCode());
        self::assertSame('[]', $res->getContent());
    }

    public function testAnonymousUserCannotWriteEvenWhenCanWriteIsTrue(): void
    {
        $Controller = new Apiv2Controller(
            new AnonymousUser(1),
            Request::create(
                '/api/v2/info',
                'POST',
                server: array('CONTENT_TYPE' => 'application/json'),
                content: '{}',
            ),
        );
        // PHP session requests normally set this to true. Anonymous users must still be read-only.
        $Controller->canWrite = true;

        $res = $Controller->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $res->getStatusCode());
    }

    public function testAnonymousUserCannotReadCompounds(): void
    {
        foreach (array('/api/v2/compounds', '/api/v2/compounds/1') as $uri) {
            $Controller = new Apiv2Controller(
                new AnonymousUser(1),
                Request::create($uri, 'GET'),
            );

            $res = $Controller->getResponse();

            self::assertSame(Response::HTTP_FORBIDDEN, $res->getStatusCode());
        }
    }

    public function testCannotReadAnotherTeamsSubmodel(): void
    {
        $Controller = new Apiv2Controller(
            $this->getRandomUserInTeam(2),
            Request::create('/api/v2/teams/1/procurement_requests', 'GET'),
        );

        $res = $Controller->getResponse();
        self::assertSame(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testCannotAccessAnotherUsersSubmodel(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller(
            $user,
            Request::create('/api/v2/users/1/uploads', 'GET'),
        );
        $res = $Controller->getResponse();
        self::assertSame(Response::HTTP_FORBIDDEN, $res->getStatusCode());
    }

    public function testAnonymousUserCannotReadAnotherTeamsSubmodel(): void
    {
        $Controller = new Apiv2Controller(
            new AnonymousUser(1),
            Request::create('/api/v2/teams/2/status', 'GET'),
        );
        $res = $Controller->getResponse();
        self::assertSame(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testCannotReadAnotherUsersRequestActions(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller(
            $user,
            Request::create('/api/v2/users/1/request_actions', 'GET'),
        );
        $res = $Controller->getResponse();
        self::assertSame(Response::HTTP_FORBIDDEN, $res->getStatusCode());
    }

    public function testAnonymousUserCanReadCurrentTeamsSubmodel(): void
    {
        $Controller = new Apiv2Controller(
            new AnonymousUser(1),
            Request::create('/api/v2/teams/1/status', 'GET'),
        );

        $res = $Controller->getResponse();
        self::assertSame(Response::HTTP_OK, $res->getStatusCode());
    }

    public function testCanReadCurrentTeamsSubmodel(): void
    {
        $Controller = new Apiv2Controller(
            $this->getRandomUserInTeam(1),
            Request::create('/api/v2/teams/1/status', 'GET'),
        );

        $res = $Controller->getResponse();
        self::assertSame(Response::HTTP_OK, $res->getStatusCode());
    }

    public function testBadJson(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/users/', 'POST', content: '{'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testBadAction(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/users/me', 'PATCH', content: '{"action": "wrong"}'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testGetBinaryFail(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/experiments/?format=binary', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testConfigNotSysadmin(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/config', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $res->getStatusCode());
    }

    public function testIncorrectContentType(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/users', 'POST', server: array('AUTHORIZATION' => 'apiKey4Test', 'CONTENT_TYPE' => 'not/valid')));
        $Controller->canWrite = true;
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testCreateApiKeyReturnsRawTokenInLocation(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller(
            $user,
            Request::create(
                '/api/v2/apikeys',
                'POST',
                server: array('CONTENT_TYPE' => 'application/json'),
                content: '{"name": "controller test key", "canwrite": 0}',
            ),
        );
        $Controller->canWrite = true;
        $res = $Controller->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $res->getStatusCode());
        $apiKey = basename((string) $res->headers->get('Location'));
        $this->assertMatchesRegularExpression('/\A[[:xdigit:]]{32}\z/', $apiKey);

        $ApiKeys = new ApiKeys($user);
        $storedKey = $ApiKeys->readFromApiKey($apiKey);
        $ApiKeys->setId((int) $storedKey['id']);
        $this->assertTrue($ApiKeys->destroy());
    }

    public function testGetCompounds(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/compounds', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_OK, $res->getStatusCode());
    }

    public function testGetExports(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/api/v2/exports', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_OK, $res->getStatusCode());
    }

    public function testGetDspace(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $controller = new Apiv2Controller($user, Request::create('/api/v2/dspace?action=foo'));
        $Config = Config::getConfig();
        $Config->configArr['dspace_host'] = 'https://example.org';
        $response = $controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true);
        $this->assertSame(400, $data['code']);
        $this->assertSame(
            'Unknown "action" value. Expected one of: getcollections, gettypes.',
            $data['message']
        );
    }
}

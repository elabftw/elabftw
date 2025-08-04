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

use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * To emulate nginx rewrite rule, query is done with req param
 */
class Apiv2ControllerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testCanwriteFalse(): void
    {
        $Controller = new Apiv2Controller($this->getRandomUserInTeam(1), Request::create('/?req=/api/v2/users', 'POST'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testInvalidEndpoint(): void
    {
        $Controller = new Apiv2Controller($this->getRandomUserInTeam(1), Request::create('/?req=/api/v2/supercalifragilisticexpialidocious', 'GET', server: array('AUTHORIZATION' => 'apiKey4Test')));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testGetResponse(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/info/me&limit=12&offset=1&search=wtf', 'GET'));
        $res = $Controller->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertEquals(Response::HTTP_OK, $res->getStatusCode());
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/teams/curent', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_OK, $res->getStatusCode());
    }

    public function testBadJson(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/users/', 'POST', content: '{'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testBadAction(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/users/me', 'PATCH', content: '{"action": "wrong"}'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testGetBinaryFail(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/experiments/&format=binary', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }

    public function testConfigNotSysadmin(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/config', 'GET'));
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $res->getStatusCode());
    }

    public function testIncorrectContentType(): void
    {
        $user = $this->getRandomUserInTeam(1);
        $Controller = new Apiv2Controller($user, Request::create('/?req=/api/v2/users', 'POST', server: array('AUTHORIZATION' => 'apiKey4Test', 'CONTENT_TYPE' => 'not/valid')));
        $Controller->canWrite = true;
        $res = $Controller->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }
}

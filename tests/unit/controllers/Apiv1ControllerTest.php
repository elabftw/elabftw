<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Models\Users\Users;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Apiv1ControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetResponse(): void
    {
        $controller = new Apiv1Controller(new Users(1, 1), new Request());
        $res = $controller->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $res->getStatusCode());
    }
}

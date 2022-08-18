<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;

class Apiv2ControllerTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
    }

    public function testReadOnly(): void
    {
        $postJsonRequest = Request::create(
            '/api/v2/users',
            'POST',
            array(),
            // cookies
            array(),
            // files
            array(),
            // server
            array('CONTENT_TYPE' => 'application/json'),
            '{"action":"create"}',
        );
        $Api = new Apiv2Controller($this->Users, $postJsonRequest);
        $resp = $Api->getResponse();
        $this->assertEquals(400, $resp->getStatusCode());
    }
}

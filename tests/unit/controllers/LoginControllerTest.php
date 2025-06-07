<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class LoginControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetResponseNoMethodProvided(): void
    {
        $LoginController = new LoginController(
            Config::getConfig(),
            Request::createFromGlobals(),
            new Session(),
            new Logger('test'),
            new Users(1, 1),
        );
        $this->expectException(ImproperActionException::class);
        $LoginController->getResponse();
    }

    public function testGetResponseMustEnableMfa(): void
    {
        $Session = new Session();
        $Session->set('enable_mfa', 'hell yeah');
        $Session->set('mfa_secret', 'EXAMPLE2FASECRET234567ABCDEFGHIJ');
        $LoginController = new LoginController(
            Config::getConfig(),
            Request::createFromGlobals(),
            $Session,
            new Logger('test'),
            new Users(1, 1),
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $res);
    }
}

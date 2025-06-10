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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $this->assertSame('/login.php', $res->headers->get('Location'));
    }

    public function testGetResponseCancelMfa(): void
    {
        $Session = new Session();
        $Session->set('enable_mfa', 'hell yeah');
        $Request = Request::create('/login.php', 'POST', array('Cancel' => 'cancel'));
        $LoginController = new LoginController(
            Config::getConfig(),
            $Request,
            $Session,
            new Logger('test'),
            new Users(1, 1),
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $res);
        $this->assertSame('/login.php', $res->headers->get('Location'));
    }

    public function testAuthLocalButDisabled(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'local');
        $Config = Config::getConfig();
        // disable local auth
        $Config->configArr['local_auth_enabled'] = '0';
        $LoginController = new LoginController(
            $Config,
            $Request,
            new Session(),
            new Logger('test'),
            new Users(1, 1),
        );
        $this->expectException(ImproperActionException::class);
        $LoginController->getResponse();
    }

    public function testAuthLocalButNothingProvided(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'local');
        $Config = Config::getConfig();
        $Config->configArr['local_auth_enabled'] = '1';
        $LoginController = new LoginController(
            $Config,
            $Request,
            new Session(),
            new Logger('test'),
            new Users(1, 1),
        );
        $this->expectException(QuantumException::class);
        $LoginController->getResponse();
    }

    public function testAuthLocal(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'local');
        $Request->request->set('email', 'toto@yopmail.com');
        $Request->request->set('password', 'totototototo');
        $Config = Config::getConfig();
        $Config->configArr['local_auth_enabled'] = '1';
        $LoginController = new LoginController(
            $Config,
            $Request,
            new Session(),
            new Logger('test'),
            new Users(1, 1),
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $res);
        $this->assertSame('/index.php', $res->headers->get('Location'));
    }

    public function testAuthAnonButNotAllowed(): void
    {
        $Session = new Session();
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'anon');
        $Request->request->set('team_id', 1);
        $Config = Config::getConfig();
        $Config->configArr['anon_users'] = '0';
        $LoginController = new LoginController(
            $Config,
            $Request,
            $Session,
            new Logger('test'),
            new Users(1, 1),
        );
        $this->expectException(IllegalActionException::class);
        $LoginController->getResponse();
    }

    public function testAuthAnon(): void
    {
        $Session = new Session();
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'anon');
        $Request->request->set('team_id', 1);
        $Config = Config::getConfig();
        $Config->configArr['anon_users'] = '1';
        $LoginController = new LoginController(
            $Config,
            $Request,
            $Session,
            new Logger('test'),
            new Users(1, 1),
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame('/index.php', $res->headers->get('Location'));
    }

    public function testAuthTeam(): void
    {
        $Session = new Session();
        $Session->set('auth_userid', 1);
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'team');
        $Request->request->set('selected_team', 1);
        $LoginController = new LoginController(
            Config::getConfig(),
            $Request,
            $Session,
            new Logger('test'),
            new Users(1, 1),
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame('/index.php', $res->headers->get('Location'));
    }
}

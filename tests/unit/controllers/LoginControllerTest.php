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

use Elabftw\Auth\CookieToken;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Params\UserParams;
use Elabftw\Services\MfaHelper;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class LoginControllerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private array $config;

    protected function setUp(): void
    {
        // base configuration
        $this->config = array(
            'remember_me_allowed' => '1',
            'enforce_mfa' => '0',
            'cookie_validity_time' => '300',
            'anon_users' => '1',
            'ldap_username' => 'admin',
            'ldap_password' => 'adm1n',
            'ldap_scheme' => 'http',
            'ldap_host' => 'ldap',
            'ldap_port' => '389',
            'ldap_base_dn' => '',
            'ldap_use_tls' => '0',
            'local_auth_enabled' => '1',
            'local_login' => '1',
            'local_login_hidden_only_sysadmin' => '0',
            'local_login_only_sysadmin' => '0',
            'max_password_age_days' => '399',
            'login_tries' => '9000',
            'external_auth_enabled' => '0',
            'lang' => 'en_GB',
        );
    }

    public function testGetResponseNoMethodProvided(): void
    {
        $LoginController = new LoginController(
            $this->config,
            Request::createFromGlobals(),
            new Session(),
        );
        $this->expectException(UnauthorizedException::class);
        $LoginController->getResponse();
    }

    public function testAuthLocalButDisabled(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'local');
        $Request->request->set('rememberme', 'on');
        // disable local auth
        $config = $this->config;
        $config['local_auth_enabled'] = '0';
        $LoginController = new LoginController(
            $config,
            $Request,
            new Session(),
        );
        $this->expectException(ImproperActionException::class);
        $LoginController->getResponse();
    }

    public function testEnableMfaFromLogin(): void
    {
        $Request = Request::createFromGlobals();
        $Session = new Session();
        $user = $this->getUserInTeam(2);
        $Session->set('auth_userid', $user->userid);
        // we do mfa auth here
        $Request->request->set('auth_type', 'mfa');
        $helper = new MfaHelper();
        // we send the secret and code, as would happen from login page when setting up mfa
        $Request->request->set('mfa_secret', $helper->secret);
        $Request->request->set('mfa_code', $helper->getCode());
        $config = $this->config;
        // enforce mfa for everyone so the gate is active
        $config['enforce_mfa'] = '3';
        $LoginController = new LoginController(
            $config,
            $Request,
            $Session,
        );
        $response = $LoginController->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($Session->has('has_verified_mfa'));
        $userData = $user->readOneFull();
        $this->assertSame($helper->secret, $userData['mfa_secret']);
        // now try to login with local auth and see that we are being redirected to login page for mfa
        $Request->request->set('auth_type', 'local');
        $Request->request->set('email', $user->userData['email']);
        // all users have the same password on test instance
        $Request->request->set('password', 'totototototo');
        // create a new session as we mimic a fresh login
        $Session->clear();
        $LoginController = new LoginController(
            $config,
            $Request,
            $Session,
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $res);
        $this->assertSame('/login.php', $res->headers->get('Location'));
        $this->assertFalse($Session->has('has_verified_mfa'));
        $this->assertTrue($Session->get('mfa_auth_required'));
        $this->assertSame($user->userid, $Session->get('auth_userid'));
        // now remove it so it doesn't cause issues later in other tests
        $user->update(new UserParams('mfa_secret', null));
    }

    public function testLoginWithCookie(): void
    {
        $Request = Request::createFromGlobals();
        $token = CookieToken::generate();
        $Request->cookies->set('token', $token);
        $Request->cookies->set('token_team', '2');
        $LoginController = new LoginController(
            $this->config,
            $Request,
            new Session(),
        );
        $this->expectException(UnauthorizedException::class);
        $LoginController->getResponse();
    }

    public function testAuthLocalButNothingProvided(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'local');
        $LoginController = new LoginController(
            $this->config,
            $Request,
            new Session(),
        );
        $this->expectException(InvalidCredentialsException::class);
        $LoginController->getResponse();
    }

    public function testAuthLocal(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'local');
        $Request->request->set('email', 'toto@yopmail.com');
        $Request->request->set('password', 'totototototo');
        $LoginController = new LoginController(
            $this->config,
            $Request,
            new Session(),
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
        $config = $this->config;
        $config['anon_users'] = '0';
        $LoginController = new LoginController(
            $config,
            $Request,
            $Session,
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
        $LoginController = new LoginController(
            $this->config,
            $Request,
            $Session,
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
            $this->config,
            $Request,
            $Session,
        );
        $res = $LoginController->getResponse();
        $this->assertInstanceOf(Response::class, $res);
        $this->assertSame('/index.php', $res->headers->get('Location'));
    }

    public function testAuthDemo(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'demo');
        $Request->request->set('email', 'user2@demo.elabftw.net');
        new LoginController(
            $this->config,
            $Request,
            new Session(),
            demoMode: true,
        )->getResponse();
    }

    public function testAuthDemoNotInDemo(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'demo');
        $this->expectException(ImproperActionException::class);
        new LoginController(
            $this->config,
            $Request,
            new Session(),
        )->getResponse();
    }

    public function testAuthDemoInvalidEmailUserNotExist(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'demo');
        $Request->request->set('email', 'sysadmin@AHAHAHAHAHAHAHAHAHA.com');
        $this->expectException(InvalidCredentialsException::class);
        new LoginController(
            $this->config,
            $Request,
            new Session(),
            demoMode: true,
        )->getResponse();
    }

    public function testAuthDemoInvalidEmail(): void
    {
        $Request = Request::createFromGlobals();
        $Request->request->set('auth_type', 'demo');
        $Request->request->set('email', 'toto@yopmail.com');
        $this->expectException(InvalidCredentialsException::class);
        new LoginController(
            $this->config,
            $Request,
            new Session(),
            demoMode: true,
        )->getResponse();
    }
}

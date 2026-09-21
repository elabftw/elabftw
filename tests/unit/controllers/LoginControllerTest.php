<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Auth\AnonymousLoginValidator;
use Elabftw\Auth\LoginFlow;
use Elabftw\Auth\MfaRateLimiter;
use Elabftw\Auth\MfaRequired;
use Elabftw\Auth\PasswordRenewalRequired;
use Elabftw\Auth\RememberMe;
use Elabftw\Auth\SamlRequestState;
use Elabftw\Auth\SelectableTeam;
use Elabftw\Auth\SelectableTeams;
use Elabftw\Auth\TeamRequestRequired;
use Elabftw\Auth\TeamSelectionRequired;
use Elabftw\Auth\UserLoginContext;
use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\Action;
use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\LoginAction;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Exceptions\TooManyMfaAttemptsException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthenticatorInterface;
use Elabftw\Interfaces\LoginStepInterface;
use Elabftw\Interfaces\MfaVerifierInterface;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Traits\TestsUtilsTrait;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use function bin2hex;
use function random_bytes;
use function str_starts_with;

final class LoginControllerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private array $config;

    protected function setUp(): void
    {
        $this->config = array(
            'remember_me_allowed' => '1',
            'cookie_validity_time' => '300',
            'anon_users' => '1',
            'allow_users_change_identity' => '1',
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
            'login_tries' => '9000',
            'external_auth_enabled' => '0',
            'saml_toggle' => '0',
            'lang' => 'en_GB',
        );
    }

    public function testGetResponseRejectsMissingAuthType(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->createController(
            Request::create('/login.php', 'POST'),
        )->getResponse();
    }

    public function testCookieAuthenticationIsNotHandledByLoginController(): void
    {
        $request = Request::create('/login.php', 'GET');
        $request->cookies->set('token', 'some-token');
        $request->cookies->set('token_team', '2');

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testAcsWithoutSamlResponseIsNotHandledAsSamlResponse(): void
    {
        $request = Request::create('/index.php?acs', 'POST');

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testLocalAuthenticationRejectsDisabledLocalAuth(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'local',
            'email' => 'toto@yopmail.com',
            'password' => 'totototototo',
        ));
        $config = $this->config;
        $config['local_auth_enabled'] = '0';

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Local authentication is disabled on this instance.');

        $this->createController($request, config: $config)->getResponse();
    }

    public function testLocalAuthenticationRejectsMissingCredentials(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'local',
        ));

        $this->expectException(InvalidCredentialsException::class);

        $this->createController($request)->getResponse();
    }

    public function testLocalAuthenticatorReturnsLocalAuthentication(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'local',
            'email' => 'toto@yopmail.com',
            'password' => 'totototototo',
        ));
        $controller = $this->createController($request);

        $authenticator = $this->invokePrivate(
            $controller,
            'getAuthenticator',
            array(LoginAction::Local),
        );
        self::assertInstanceOf(
            AuthenticatorInterface::class,
            $authenticator,
        );

        $authentication = $authenticator->authenticate();

        if (!$authentication instanceof Authentication) {
            self::fail(
                'Local authentication must return an Authentication.',
            );
        }

        self::assertGreaterThan(0, $authentication->userid);
        self::assertSame(AuthMethod::Local, $authentication->method);
    }

    public function testDemoAuthenticatorReturnsDemoAuthentication(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'demo',
            'email' => 'user2@demo.elabftw.net',
        ));
        $controller = $this->createController(
            $request,
            demoMode: true,
        );

        $authenticator = $this->invokePrivate(
            $controller,
            'getAuthenticator',
            array(LoginAction::Demo),
        );
        self::assertInstanceOf(
            AuthenticatorInterface::class,
            $authenticator,
        );

        $authentication = $authenticator->authenticate();

        if (!$authentication instanceof Authentication) {
            self::fail(
                'Demo authentication must return an Authentication.',
            );
        }

        self::assertGreaterThan(0, $authentication->userid);
        self::assertSame(AuthMethod::Demo, $authentication->method);
    }

    public function testDemoAuthenticationRequiresDemoMode(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'demo',
            'email' => 'user2@demo.elabftw.net',
        ));

        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('This instance is not in demo mode.');

        $this->createController($request)->getResponse();
    }

    public function testDemoAuthenticationRejectsInvalidUser(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'demo',
            'email' => 'sysadmin@AHAHAHAHAHAHAHAHAHA.com',
        ));

        $this->expectException(InvalidCredentialsException::class);

        $this->createController(
            $request,
            demoMode: true,
        )->getResponse();
    }

    public function testAnonymousLoginRejectsWhenAnonymousAccessIsDisabled(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'anon',
            'team_id' => 1,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController(
            $request,
            anonymousLoginValidator: new AnonymousLoginValidator(false),
        )->getResponse();
    }

    public function testAnonymousLoginRejectsUnknownTeam(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'anon',
            'team_id' => 0,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController(
            $request,
            anonymousLoginValidator: new AnonymousLoginValidator(true),
        )->getResponse();
    }

    public function testAnonymousLoginRejectsInvisibleTeam(): void
    {
        $Team = new Teams($this->getUserInTeam(1, 1), 1);
        $wasVisible = $Team->teamArr['visible'];
        $Team->patch(Action::Update, array('visible' => 0));

        try {
            try {
                $Validator = new AnonymousLoginValidator(true);
                $Validator->validate(1);
                self::fail('Anonymous login to an invisible team should be rejected.');
            } catch (UnauthorizedException) {
                self::addToAssertionCount(1);
            }
        } finally {
            $Team->patch(Action::Update, array('visible' => $wasVisible));
        }
    }

    public function testAnonymousLoginCompletesLogin(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setId('fixedsessionid1234567890');
        $session->start();
        $sessionId = $session->getId();
        $session->set('auth_userid', 123);
        $session->set('auth_method', AuthMethod::Local->value);

        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'anon',
            'team_id' => 1,
        ));

        $response = $this->createController(
            $request,
            session: $session,
            anonymousLoginValidator: new AnonymousLoginValidator(true),
        )->getResponse();

        self::assertRedirect($response, '/index.php');
        self::assertNotSame($sessionId, $session->getId());
        self::assertFalse($session->has('auth_userid'));
        self::assertFalse($session->has('auth_method'));
    }

    public function testMfaActionRequiresMfaPendingState(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'mfa',
            'mfa_code' => '123456',
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testMfaActionPassesPendingUserAndCodeToVerifier(): void
    {
        $session = new Session();
        $session->set('mfa_auth_required', true);
        $session->set('auth_userid', 1);
        $session->set('auth_method', AuthMethod::Local->value);

        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'mfa',
            'mfa_code' => '123456',
        ));

        $mfaVerifier = $this->createMock(MfaVerifierInterface::class);
        $mfaVerifier->expects(self::once())
            ->method('verify')
            ->with(1, '123456')
            ->willThrowException(new RuntimeException('stop-after-verification'));

        try {
            $this->createController(
                $request,
                session: $session,
                mfaVerifier: $mfaVerifier,
            )->getResponse();
            self::fail('Expected verifier exception.');
        } catch (RuntimeException $e) {
            self::assertSame('stop-after-verification', $e->getMessage());
        }

        // Failed verification must keep the pending MFA state intact.
        self::assertTrue($session->has('mfa_auth_required'));
    }

    public function testMfaRateLimitClearsPendingLoginState(): void
    {
        $session = new Session();
        $session->set('mfa_auth_required', true);
        $session->set('auth_userid', 1);
        $session->set(
            'auth_method',
            AuthMethod::Local->value,
        );

        $request = Request::create(
            '/login.php',
            'POST',
            array(
                'auth_type' => 'mfa',
                'mfa_code' => '123456',
            ),
        );

        // One failure is enough for this controller test.
        $rateLimiter = new MfaRateLimiter(1);
        $rateLimiter->clear(1);

        $mfaVerifier = $this->createMock(
            MfaVerifierInterface::class,
        );
        $mfaVerifier->expects(self::once())
            ->method('verify')
            ->with(1, '123456')
            ->willThrowException(
                new InvalidMfaCodeException(),
            );

        try {
            $this->createController(
                $request,
                session: $session,
                mfaVerifier: $mfaVerifier,
                mfaRateLimiter: $rateLimiter,
            )->getResponse();
            self::fail('Expected MFA rate-limit exception.');
        } catch (TooManyMfaAttemptsException) {
            self::assertFalse($session->has('mfa_auth_required'));
            self::assertFalse($session->has('auth_userid'));
            self::assertFalse($session->has('auth_method'));
        } finally {
            $rateLimiter->clear(1);
        }
    }

    public function testTeamSelectionRequiresPendingSelectionState(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'team',
            'selected_team' => 1,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testTeamRequestRequiresPendingRequestState(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'teamselection',
            'team_id' => 1,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testTeamRequestRejectsInvisibleTeamBeforeChangingMembership(): void
    {
        $session = new Session();
        $this->storePendingAuthentication($session);
        $session->set('team_request_selection_required', true);

        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'teamselection',
            'team_id' => 0,
        ));

        $this->expectException(ForbiddenException::class);

        $this->createController(
            $request,
            session: $session,
        )->getResponse();
    }

    public function testInitialTeamSelectionRequiresPendingState(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'teaminit',
            'team_id' => 1,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testInitialTeamSelectionRequiresPendingUserInfo(): void
    {
        $session = new Session();
        $session->set('initial_team_selection_required', true);

        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'teaminit',
            'team_id' => 1,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController(
            $request,
            session: $session,
        )->getResponse();
    }

    public function testInitialTeamSelectionCreatesUserAndClearsPendingState(): void
    {
        $email = 'login-controller-' . bin2hex(random_bytes(6)) . '@example.com';
        $session = new Session();
        $session->set('initial_team_selection_required', true);
        $session->set('teaminit_user_info', array(
            'email' => $email,
            'firstname' => 'Login',
            'lastname' => 'Controller',
            'orgid' => null,
            'orcid' => null,
        ));

        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'teaminit',
            'team_id' => 1,
            'teaminit_firstname' => 'Edited',
            'teaminit_lastname' => 'Name',
        ));

        $response = $this->createController(
            $request,
            session: $session,
        )->getResponse();

        self::assertRedirect($response, '/login.php');
        self::assertTrue($session->get('teaminit_done'));
        self::assertTrue($session->has('teaminit_done_need_validation'));
        self::assertFalse($session->has('initial_team_selection_required'));
        self::assertFalse($session->has('teaminit_user_info'));
        self::assertSame(
            $email,
            ExistingUser::fromEmail($email)->userData['email'],
        );
        $user = ExistingUser::fromEmail($email);
        self::assertSame(
            'Edited',
            $user->userData['firstname'],
        );
        self::assertSame(
            'Name',
            $user->userData['lastname'],
        );
    }

    public function testInitialTeamSelectionKeepsIdentityWhenChangesDisabled(): void
    {
        $email = 'login-controller-' . bin2hex(random_bytes(6)) . '@example.com';
        $session = new Session();
        $session->set('initial_team_selection_required', true);
        $session->set('teaminit_user_info', array(
            'email' => $email,
            'firstname' => 'Login',
            'lastname' => 'Controller',
            'orgid' => null,
            'orcid' => null,
        ));

        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'teaminit',
            'team_id' => 1,
            'teaminit_firstname' => 'Forged',
            'teaminit_lastname' => 'Identity',
        ));
        $config = $this->config;
        $config['allow_users_change_identity'] = '0';

        $response = $this->createController(
            $request,
            session: $session,
            config: $config,
        )->getResponse();

        self::assertRedirect($response, '/login.php');
        $user = ExistingUser::fromEmail($email);
        self::assertSame(
            'Login',
            $user->userData['firstname'],
        );
        self::assertSame(
            'Controller',
            $user->userData['lastname'],
        );
    }

    public function testSamlStartRejectsDisabledSaml(): void
    {
        $request = Request::create('/login.php', 'POST', array(
            'auth_type' => 'saml',
            'idpId' => 1,
        ));

        $this->expectException(UnauthorizedException::class);

        $this->createController($request)->getResponse();
    }

    public function testMfaRequiredStepStoresPendingAuthentication(): void
    {
        $session = new Session();
        $controller = $this->createController(
            Request::create('/login.php', 'POST'),
            session: $session,
        );
        $authentication = new Authentication(1, AuthMethod::Local);

        $response = $this->handleLoginStep(
            $controller,
            $this->hydrateLoginStep(
                MfaRequired::class,
                array('authentication' => $authentication),
            ),
        );

        self::assertRedirect($response, '/login.php');
        self::assertTrue($session->get('mfa_auth_required'));
        self::assertSame(1, $session->get('auth_userid'));
        self::assertSame(AuthMethod::Local->value, $session->get('auth_method'));
    }

    public function testPasswordRenewalRequiredStepStoresPendingAuthentication(): void
    {
        $session = new Session();
        $controller = $this->createController(
            Request::create('/login.php', 'POST'),
            session: $session,
        );
        $authentication = new Authentication(1, AuthMethod::Local);

        $response = $this->handleLoginStep(
            $controller,
            $this->hydrateLoginStep(
                PasswordRenewalRequired::class,
                array('authentication' => $authentication),
            ),
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue(
            str_starts_with(
                (string) $response->headers->get('Location'),
                '/change-pass.php?renewal=1&key=',
            ),
        );
        self::assertTrue($session->get('renew_password_required'));
        self::assertSame(1, $session->get('auth_userid'));
        self::assertSame(AuthMethod::Local->value, $session->get('auth_method'));
    }

    public function testTeamRequestRequiredStepStoresPendingAuthentication(): void
    {
        $session = new Session();
        $controller = $this->createController(
            Request::create('/login.php', 'POST'),
            session: $session,
        );
        $authentication = new Authentication(1, AuthMethod::Saml);

        $response = $this->handleLoginStep(
            $controller,
            $this->hydrateLoginStep(
                TeamRequestRequired::class,
                array('authentication' => $authentication),
            ),
        );

        self::assertRedirect($response, '/login.php');
        self::assertTrue($session->get('team_request_selection_required'));
        self::assertSame(1, $session->get('auth_userid'));
        self::assertSame(AuthMethod::Saml->value, $session->get('auth_method'));
    }

    public function testTeamSelectionRequiredStepStoresTeamsAndAuthentication(): void
    {
        $session = new Session();
        $controller = $this->createController(
            Request::create('/login.php', 'POST'),
            session: $session,
        );
        $authentication = new Authentication(1, AuthMethod::Ldap);
        $teams = new SelectableTeams(array(
            new SelectableTeam(1, 'Alpha', false, false),
            new SelectableTeam(2, 'Bravo', false, true),
        ));

        $response = $this->handleLoginStep(
            $controller,
            $this->hydrateLoginStep(
                TeamSelectionRequired::class,
                array(
                    'authentication' => $authentication,
                    'teams' => $teams,
                ),
            ),
        );

        self::assertRedirect($response, '/login.php');
        self::assertTrue($session->get('team_selection_required'));
        self::assertSame($teams->all(), $session->get('team_selection'));
        self::assertSame(1, $session->get('auth_userid'));
        self::assertSame(AuthMethod::Ldap->value, $session->get('auth_method'));
    }

    public function testUserLoginContextCompletesLoginAndClearsPendingAuthentication(): void
    {
        $session = new Session();
        $this->storePendingAuthentication($session);
        $controller = $this->createController(
            Request::create('/login.php', 'POST'),
            session: $session,
        );

        $context = new UserLoginContext(
            1,
            1,
            AuthMethod::Local,
        );

        $response = $this->handleLoginStep($controller, $context);

        self::assertRedirect($response, '/index.php');
        self::assertFalse($session->has('auth_userid'));
        self::assertFalse($session->has('auth_method'));
    }

    public function testUnknownLoginStepThrowsLogicException(): void
    {
        $controller = $this->createController(
            Request::create('/login.php', 'POST'),
        );
        $step = new class implements LoginStepInterface {};

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown login step.');

        $this->handleLoginStep($controller, $step);
    }

    public function testPasswordRenewalIsAllowedAsQueryAction(): void
    {
        $request = Request::create(
            '/app/controllers/LoginController.php?action=password_renewal',
            'GET',
        );

        $controller = $this->createController($request);

        $action = $this->invokePrivate(
            $controller,
            'getLoginAction',
        );

        self::assertSame(
            LoginAction::PasswordRenewal,
            $action,
        );
    }

    public function testOtherLoginActionsAreRejectedFromQuery(): void
    {
        $request = Request::create(
            '/app/controllers/LoginController.php?action=team',
            'GET',
        );

        $controller = $this->createController($request);

        $this->expectException(
            UnauthorizedException::class,
        );

        $this->invokePrivate(
            $controller,
            'getLoginAction',
        );
    }

    private function createController(
        Request $request,
        ?Session $session = null,
        ?MfaVerifierInterface $mfaVerifier = null,
        ?MfaRateLimiter $mfaRateLimiter = null,
        ?AnonymousLoginValidator $anonymousLoginValidator = null,
        ?array $config = null,
        bool $demoMode = false,
    ): LoginController {
        return new LoginController(
            $config ?? $this->config,
            $request,
            $session ?? new Session(),
            $this->unusedLoginFlow(),
            $mfaVerifier ?? $this->createStub(MfaVerifierInterface::class),
            $mfaRateLimiter ?? new MfaRateLimiter(),
            $anonymousLoginValidator ?? $this->unusedAnonymousLoginValidator(),
            new RememberMe($request, true),
            new SamlRequestState($request),
            $demoMode,
        );
    }

    private function unusedLoginFlow(): LoginFlow
    {
        /** @var LoginFlow */
        return new ReflectionClass(LoginFlow::class)->newInstanceWithoutConstructor();
    }

    private function unusedAnonymousLoginValidator(): AnonymousLoginValidator
    {
        /** @var AnonymousLoginValidator */
        return new ReflectionClass(AnonymousLoginValidator::class)->newInstanceWithoutConstructor();
    }

    private function handleLoginStep(
        LoginController $controller,
        LoginStepInterface $step,
    ): Response {
        /** @var Response */
        return $this->invokePrivate(
            $controller,
            'handleLoginStep',
            array($step),
        );
    }

    /**
     * Build a login step without coupling these controller tests to DTO constructor
     * signatures. The controller contract is the public readonly properties it reads.
     *
     * @param class-string<LoginStepInterface> $class
     * @param array<string, mixed> $properties
     */
    private function hydrateLoginStep(
        string $class,
        array $properties,
    ): LoginStepInterface {
        $reflection = new ReflectionClass($class);
        /** @var LoginStepInterface $step */
        $step = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            $reflection->getProperty($name)->setValue($step, $value);
        }

        return $step;
    }

    private function invokePrivate(
        object $object,
        string $method,
        array $arguments = array(),
    ): mixed {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }

    private function storePendingAuthentication(Session $session): void
    {
        $session->set('auth_userid', 1);
        $session->set('auth_method', AuthMethod::Local->value);
    }

    private static function assertRedirect(
        Response $response,
        string $location,
    ): void {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($location, $response->headers->get('Location'));
    }
}

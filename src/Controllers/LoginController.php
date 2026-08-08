<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Auth\AnonymousLoginContext;
use Elabftw\Auth\AnonymousLoginValidator;
use Elabftw\Auth\Demo;
use Elabftw\Auth\External;
use Elabftw\Auth\InitialTeamSelectionRequired;
use Elabftw\Auth\Ldap;
use Elabftw\Auth\Local;
use OneLogin\Saml2\Response as SamlResponse;
use OneLogin\Saml2\Settings as SamlSettings;
use Elabftw\Auth\LoginFlow;
use Elabftw\Auth\MfaRequired;
use Elabftw\Auth\PasswordRenewalRequired;
use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Auth\TeamRequestRequired;
use Elabftw\Auth\TeamSelectionRequired;
use Elabftw\Auth\UserLoginContext;
use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\IdpsHelper;
use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\AuthType;
use Elabftw\Enums\Language;
use Elabftw\Enums\LoginAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthenticatorInterface;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Interfaces\LoginStepInterface;
use Elabftw\Interfaces\MfaVerifierInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Idps;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users2Teams;
use Elabftw\Services\DeviceToken;
use Elabftw\Services\DeviceTokenValidator;
use Elabftw\Services\Filter;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\ResetPasswordKey;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use LogicException;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Override;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

use function setcookie;
use function time;
use function htmlspecialchars;
use function is_array;
use function session_get_cookie_params;
use function sprintf;
use function explode;

/**
 * For all your authentication/login needs
 */
final class LoginController implements ControllerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly Request $Request,
        private readonly FlashBagAwareSessionInterface $Session,
        private readonly LoginFlow $loginFlow,
        private readonly MfaVerifierInterface $mfaVerifier,
        private readonly AnonymousLoginValidator $anonymousLoginValidator,
        private readonly bool $demoMode = false,
    ) {}

    #[Override]
    public function getResponse(): Response
    {
        return match ($this->getLoginAction()) {
            LoginAction::Authenticate => $this->handleAuthentication(),
            LoginAction::Anonymous => $this->handleAnonymousLogin(),
            LoginAction::Mfa => $this->handleMfa(),
            LoginAction::SamlStart => $this->handleSamlStart(),
            LoginAction::SelectTeam => $this->handleTeamSelection(),
            LoginAction::JoinTeam => $this->handleTeamRequestSelection(),
            LoginAction::InitTeam => $this->handleInitialTeamSelection(),
            LoginAction::SamlResponse => $this->handleSamlResponse(),
        };
    }

    private function getLoginAction(): LoginAction
    {
        if (
            $this->Request->query->has('acs')
            && $this->Request->request->has('SAMLResponse')
        ) {
            return LoginAction::SamlResponse;
        }

        $authType = AuthType::tryFrom(
            $this->Request->request->getAlpha('auth_type'),
        );

        return match ($authType) {
            AuthType::Anonymous => LoginAction::Anonymous,
            AuthType::Mfa => LoginAction::Mfa,
            AuthType::Team => LoginAction::SelectTeam,
            AuthType::TeamSelection => LoginAction::JoinTeam,
            AuthType::TeamInit => LoginAction::InitTeam,
            AuthType::Saml => LoginAction::SamlStart,

            AuthType::Local,
            AuthType::Ldap,
            AuthType::External,
            AuthType::Demo => LoginAction::Authenticate,

            default => throw new UnauthorizedException(),
        };
    }

    private function handleAuthentication(): Response
    {
        $authentication = $this->getAuthenticator()->authenticate();

        return $this->handleLoginStep(
            $this->loginFlow->start($authentication),
        );
    }

    private function getAuthenticator(): AuthenticatorInterface
    {
        $authType = AuthType::tryFrom(
            $this->Request->request->getAlpha('auth_type'),
        );

        return match ($authType) {
            AuthType::Local => $this->getLocalAuthenticator(),
            AuthType::Ldap => $this->getLdapAuthenticator(),
            AuthType::External => new External(
                $this->config,
                $this->Request->server->all(),
            ),
            AuthType::Demo => $this->getDemoAuthenticator(),
            default => throw new UnauthorizedException(),
        };
    }

    private function handleLoginStep(LoginStepInterface $step): Response
    {
        return match (true) {
            $step instanceof MfaRequired => $this->requireMfa($step),
            $step instanceof PasswordRenewalRequired => $this->requirePasswordRenewal($step),
            $step instanceof TeamSelectionRequired => $this->requireTeamSelection($step),
            $step instanceof TeamRequestRequired => $this->requireTeamRequest($step),
            $step instanceof UserLoginContext => $this->completeLogin($step),
            default => throw new LogicException('Unknown login step.'),
        };
    }

    private function requireMfa(MfaRequired $step): Response
    {
        $this->storePendingAuthentication($step->authentication);

        $this->Session->set('mfa_auth_required', true);

        return new RedirectResponse('/login.php');
    }

    private function requirePasswordRenewal(
        PasswordRenewalRequired $step,
    ): Response {
        $this->storePendingAuthentication($step->authentication);

        $this->Session->set('renew_password_required', true);

        $user = new Users($step->authentication->userid);

        $key = new ResetPasswordKey(
            time(),
            Env::asString('SECRET_KEY'),
        )->generate($user->userData['email']);

        return new RedirectResponse('/change-pass.php?key=' . $key);
    }

    private function requireTeamSelection(
        TeamSelectionRequired $step,
    ): Response {
        $this->storePendingAuthentication($step->authentication);

        $this->Session->set('team_selection_required', true);
        $this->Session->set(
            'team_selection',
            $step->teams->all(),
        );

        return new RedirectResponse('/login.php');
    }

    private function requireTeamRequest(
        TeamRequestRequired $step,
    ): Response {
        $this->storePendingAuthentication($step->authentication);

        $this->Session->set('team_request_selection_required', true);

        return new RedirectResponse('/login.php');
    }

    private function handleAnonymousLogin(): Response
    {
        $teamId = $this->Request->request->getInt('team_id');

        $this->anonymousLoginValidator->validate($teamId);

        return $this->completeLogin(
            new AnonymousLoginContext(
                $teamId,
                Language::EnglishGB,
            ),
        );
    }

    private function handleMfa(): Response
    {
        if (!$this->Session->has('mfa_auth_required')) {
            throw new UnauthorizedException();
        }

        // clicking Cancel button on mfa page
        if ($this->Request->get('Cancel') === 'cancel') {
            $this->Session->getFlashBag()->add('warning', _('Authentication flow was interrupted.'));
            $this->Session->remove('mfa_auth_required');
            return new RedirectResponse('/login.php');
        }


        $authentication = $this->getPendingAuthentication();

        $this->mfaVerifier->verify(
            $authentication->userid,
            $this->Request->request->getAlnum('mfa_code'),
        );

        $this->Session->remove('mfa_auth_required');

        return $this->handleLoginStep(
            $this->loginFlow->afterMfa($authentication),
        );
    }

    private function handleTeamSelection(): Response
    {
        if (!$this->Session->has('team_selection_required')) {
            throw new UnauthorizedException();
        }

        $authentication = $this->getPendingAuthentication();

        $context = $this->loginFlow->selectTeam(
            $authentication,
            $this->Request->request->getInt('selected_team'),
        );

        $this->Session->remove('team_selection_required');
        $this->Session->remove('team_selection');

        return $this->completeLogin($context);
    }

    private function completeLogin(UserLoginContext|AnonymousLoginContext $context): Response
    {
        new LoginHelper(
            $context,
            $this->Session,
            (int) $this->config['cookie_validity_time'],
        )->login($this->setRememberMeCookie());

        $this->clearPendingAuthentication();

        return new RedirectResponse('/index.php');
    }

    private function handleTeamRequestSelection(): Response
    {
        if (!$this->Session->has('team_request_selection_required')) {
            throw new UnauthorizedException();
        }
        $authentication = $this->getPendingAuthentication();
        $teamId = $this->Request->request->getInt('team_id');

        // Users may only request a visible team.
        new TeamsHelper($teamId)->teamIsVisibleOrExplode();

        new Users2Teams(new Users($authentication->userid))
            ->create($authentication->userid, $teamId);

        $this->Session->remove('team_request_selection_required');

        return $this->handleLoginStep(
            $this->loginFlow->afterPasswordRenewal($authentication),
        );
    }

    private function getDemoAuthenticator(): AuthenticatorInterface
    {
        if (!$this->demoMode) {
            throw new ImproperActionException(
                'This instance is not in demo mode. Set DEMO_MODE=true to allow demo mode login.',
            );
        }

        return new Demo(
            $this->Request->request->getString('email'),
        );
    }

    private function getLocalAuthenticator(): AuthenticatorInterface
    {
        if ($this->config['local_auth_enabled'] === '0') {
            throw new ImproperActionException(
                'Local authentication is disabled on this instance.',
            );
        }

        $this->validateDeviceToken();

        return new Local(
            $this->Request->request->getString('email'),
            $this->Request->request->getString('password'),
            (bool) $this->config['local_login'],
            (bool) $this->config['local_login_hidden_only_sysadmin'],
            (bool) $this->config['local_login_only_sysadmin'],
            (int) $this->config['login_tries'],
        );
    }

    private function handleInitialTeamSelection(): Response
    {
        if (!$this->Session->has('initial_team_selection_required')) {
            throw new UnauthorizedException();
        }
        $teamId = $this->Request->request->getInt('team_id');
        new TeamsHelper($teamId)->teamIsVisibleOrExplode();

        $info = $this->Session->get('teaminit_user_info');
        if (!is_array($info)) {
            throw new UnauthorizedException();
        }

        $user = ExistingUser::fromScratch(
            $info['email'],
            array($teamId),
            $info['firstname'],
            $info['lastname'],
            orgid: $info['orgid'] ?? null,
            orcid: $info['orcid'] ?? null,
        );

        $this->Session->set('teaminit_done', true);
        $this->Session->set(
            'teaminit_done_need_validation',
            (string) $user->needValidation,
        );

        $this->Session->remove('initial_team_selection_required');
        $this->Session->remove('teaminit_user_info');

        return new RedirectResponse('/login.php');
    }

    private function handleSamlResponse(): Response
    {
        $IdpsHelper = new IdpsHelper(
            Config::getConfig(),
            new Idps(new Users()),
        );

        // We first need temporary settings to decode the response and discover
        // which IdP sent it.
        $temporarySettings = $IdpsHelper->getSettings();

        $response = new SAMLResponse(
            new SamlSettings($temporarySettings),
            $this->Request->request->getString('SAMLResponse'),
        );

        $issuers = $response->getIssuers();
        if (empty($issuers)) {
            throw new ImproperActionException(
                'Could not find an Issuer in the response sent by the IdP!',
            );
        }

        $settings = $IdpsHelper->getSettingsByEntityId($issuers[0]);
        $idpId = (int) $settings['idp_id'];

        $authenticator = new SamlAuth(
            new SamlAuthLib($settings),
            $this->config,
            $settings,
        );

        $result = $authenticator->assertIdpResponse();

        if ($result instanceof InitialTeamSelectionRequired) {
            $this->Session->set(
                'initial_team_selection_required',
                true,
            );
            $this->Session->set(
                'teaminit_user_info',
                $result->toArray(),
            );

            return $this->samlRedirect('/login.php');
        }

        $this->setSamlToken(
            $authenticator->encodeToken($idpId),
        );

        $response = $this->handleLoginStep(
            $this->loginFlow->start($result),
        );

        // Keep the existing SAML behavior: use a first-party meta refresh
        // rather than redirecting directly from the ACS POST.
        if ($response instanceof RedirectResponse) {
            return $this->samlRedirect(
                $response->getTargetUrl(),
            );
        }

        return $response;
    }

    private function storePendingAuthentication(Authentication $authentication): void
    {
        $this->Session->set('auth_userid', $authentication->userid);
        $this->Session->set('auth_method', $authentication->method->value);
    }

    private function getPendingAuthentication(): Authentication
    {
        return new Authentication(
            $this->Session->get('auth_userid'),
            AuthMethod::from($this->Session->get('auth_method')),
        );
    }

    private function clearPendingAuthentication(): void
    {
        $this->Session->remove('auth_userid');
        $this->Session->remove('auth_method');
    }

    private function getLdapAuthenticator(): AuthenticatorInterface
    {
        $ldapPassword = empty($this->config['ldap_password'])
            ? null
            : $this->config['ldap_password'];

        $ldapConfig = array(
            'protocol' => $this->config['ldap_scheme'] . '://',
            'hosts' => explode(',', $this->config['ldap_host']),
            'port' => (int) $this->config['ldap_port'],
            'base_dn' => $this->config['ldap_base_dn'],
            'username' => $this->config['ldap_username'],
            'password' => $ldapPassword,
            'use_tls' => (bool) $this->config['ldap_use_tls'],
        );

        return new Ldap(
            new Connection($ldapConfig),
            new Entry(),
            $this->config,
            $this->Request->request->getString('email'),
            $this->Request->request->getString('password'),
        );
    }

    /**
     * Store the rememberme choice in a cookie, not the session as it won't follow up for saml
     */
    private function setRememberMeCookie(): bool
    {
        if ($this->config['remember_me_allowed'] === '0') {
            return false;
        }
        // avoid setting it if it's present
        if ($this->Request->cookies->has('icanhazcookies')) {
            return $this->Request->cookies->getBoolean('icanhazcookies');
        }
        $icanhazcookies = $this->Request->request->has('rememberme') ? '1' : '0';
        $cookieOptions = array(
            'expires' => time() + 300,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        setcookie('icanhazcookies', $icanhazcookies, $cookieOptions);
        return $icanhazcookies === '1';
    }

    /**
     * See https://owasp.org/www-community/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies
     */
    private function validateDeviceToken(): void
    {
        // skip for multi team auth
        if ($this->Session->has('auth_userid')) {
            return;
        }
        // need the targeted user before validating the device token
        $email = Filter::sanitizeEmail($this->Request->request->getString('email'));
        try {
            $Users = ExistingUser::fromEmail($email);
        } catch (ResourceNotFoundException) {
            throw new InvalidCredentialsException();
        }
        // a devicetoken cookie might or might not exist, so this can be null
        $token = $this->Request->cookies->getString('devicetoken');
        // if a token is sent, we need to validate it
        $DeviceTokenValidator = new DeviceTokenValidator(DeviceToken::getConfig(), $token, $Users->getUserid());
        // if the token is not valid, verify we can login from untrusted devices for that user
        if ($DeviceTokenValidator->validate() == false && $Users->allowUntrustedLogin() === false) {
            // reject any attempt whatsoever if this account is locked for untrusted devices
            throw new InvalidDeviceTokenException();
        }
    }

    private function setSamlToken(string $token): void
    {
        $cookieOptions = array(
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        );

        $rememberMe = $this->config['remember_me_allowed'] === '1';
        $sessionOptions = session_get_cookie_params();

        if ($rememberMe) {
            $cookieOptions['expires'] = time()
                + 60 * (int) $this->config['cookie_validity_time'];
        } elseif ($sessionOptions['lifetime'] > 0) {
            $cookieOptions['expires'] = time() + $sessionOptions['lifetime'];
        }

        setcookie('saml_token', $token, $cookieOptions);
    }

    private function samlRedirect(string $location): Response
    {
        return new Response(
            sprintf(
                "<html><head><meta http-equiv='refresh' content='1;url=%s' />"
                . '<title>You are being redirected...</title></head>'
                . '<body>You are being redirected...</body></html>',
                htmlspecialchars($location, ENT_QUOTES),
            ),
        );
    }

    private function handleSamlStart(): Response
    {
        if ($this->config['saml_toggle'] !== '1') {
            throw new UnauthorizedException();
        }

        $idpsHelper = new IdpsHelper(
            Config::getConfig(),
            new Idps(new Users()),
        );

        $settings = $idpsHelper->getSettings(
            $this->Request->request->getInt('idpId'),
        );

        $returnUrl = $settings['baseurl'] . '/index.php?acs';
        // adding stay: true to login() will make psalm/phpstan happy but breaks saml auth
        new SamlAuthLib($settings)->login($returnUrl);
        // ^-- this will run exit()
        /** @psalm-suppress UnevaluatedCode */
        throw new LogicException('SAML login did not redirect.'); // @phpstan-ignore-line
    }
}

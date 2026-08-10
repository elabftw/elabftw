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
use Elabftw\Auth\LoginHelper;
use Elabftw\Auth\MfaRateLimiter;
use Elabftw\Auth\MfaRequired;
use Elabftw\Auth\PasswordRenewalRequired;
use Elabftw\Auth\RememberMe;
use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Auth\SamlRequestState;
use Elabftw\Auth\TeamRequestRequired;
use Elabftw\Auth\TeamSelectionRequired;
use Elabftw\Auth\UserLoginContext;
use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\IdpsHelper;
use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\Language;
use Elabftw\Enums\LoginAction;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\InvalidMfaCodeException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\TooManyMfaAttemptsException;
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
use function rawurldecode;
use function str_contains;
use function str_starts_with;
use function str_replace;

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
        private readonly MfaRateLimiter $mfaRateLimiter,
        private readonly AnonymousLoginValidator $anonymousLoginValidator,
        private readonly RememberMe $rememberMe,
        private readonly SamlRequestState $samlRequestState,
        private readonly bool $demoMode = false,
    ) {}

    #[Override]
    public function getResponse(): Response
    {
        $loginAction = $this->getLoginAction();
        return match ($loginAction) {
            LoginAction::Anonymous => $this->handleAnonymousLogin(),
            LoginAction::Mfa => $this->handleMfa(),
            LoginAction::SelectTeam => $this->handleTeamSelection(),
            LoginAction::JoinTeam => $this->handleTeamRequestSelection(),
            LoginAction::InitTeam => $this->handleInitialTeamSelection(),
            LoginAction::SamlStart => $this->handleSamlStart(),
            LoginAction::PasswordRenewal => $this->handlePasswordRenewal(),
            LoginAction::SamlResponse => $this->handleSamlResponse(),

            LoginAction::Local,
            LoginAction::Ldap,
            LoginAction::External,
            LoginAction::Demo => $this->handleAuthentication($loginAction),
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

        if ($this->Request->query->has('action')) {
            if (
                $this->Request->query->getString('action')
                    !== LoginAction::PasswordRenewal->value
            ) {
                throw new UnauthorizedException();
            }

            return LoginAction::PasswordRenewal;
        }

        return LoginAction::tryFrom(
            $this->Request->request->getAlpha('auth_type'),
        ) ?? throw new UnauthorizedException();

    }

    private function handleAuthentication(LoginAction $action): Response
    {
        $this->clearPendingLoginState();

        $this->rememberMe->capture();

        $result = $this->getAuthenticator($action)->authenticate();

        if ($result instanceof InitialTeamSelectionRequired) {
            $this->storeInitialTeamSelection($result);

            return new RedirectResponse('/login.php');
        }

        return $this->handleLoginStep(
            $this->loginFlow->start($result),
        );
    }

    private function storeInitialTeamSelection(
        InitialTeamSelectionRequired $result,
    ): void {
        $this->Session->set(
            'initial_team_selection_required',
            true,
        );

        $this->Session->set(
            'teaminit_user_info',
            $result->toArray(),
        );
    }

    private function clearPendingLoginState(): void
    {
        foreach (array(
            'auth_userid',
            'auth_method',
            'mfa_auth_required',
            'mfa_secret',
            'renew_password_required',
            'password_renewal_completed',
            'team_selection_required',
            'team_selection',
            'team_request_selection_required',
            'initial_team_selection_required',
            'teaminit_user_info',
        ) as $key) {
            $this->Session->remove($key);
        }
    }

    private function getAuthenticator(LoginAction $action): AuthenticatorInterface
    {
        return match ($action) {
            LoginAction::Local => $this->getLocalAuthenticator(),
            LoginAction::Ldap => $this->getLdapAuthenticator(),
            LoginAction::External => new External(
                $this->config,
                $this->Request->server->all(),
            ),
            LoginAction::Demo => $this->getDemoAuthenticator(),
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

    private function handlePasswordRenewal(): Response
    {
        if (!$this->Session->has('password_renewal_completed')) {
            throw new UnauthorizedException();
        }

        if (
            !$this->Session->has('auth_userid')
            || !$this->Session->has('auth_method')
        ) {
            throw new UnauthorizedException();
        }

        $authentication = $this->getPendingAuthentication();

        // This continuation must only be consumable once.
        $this->Session->remove('password_renewal_completed');

        return $this->handleLoginStep(
            $this->loginFlow->afterPasswordRenewal(
                $authentication,
            ),
        );
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

        return new RedirectResponse('/change-pass.php?renewal=1&key=' . $key);
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
        $this->clearPendingLoginState();

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
        if ($this->Request->request->get('Cancel') === 'cancel') {
            $this->Session->clear();
            return new RedirectResponse('/login.php');
        }


        $authentication = $this->getPendingAuthentication();

        if (
            $this->mfaRateLimiter->isBlocked(
                $authentication->userid,
            )
        ) {
            $this->clearPendingLoginState();

            throw new TooManyMfaAttemptsException();
        }

        try {
            $this->mfaVerifier->verify(
                $authentication->userid,
                $this->Request->request->getAlnum(
                    'mfa_code',
                ),
            );
        } catch (InvalidMfaCodeException $e) {
            if (
                $this->mfaRateLimiter->registerFailure(
                    $authentication->userid,
                )
            ) {
                $this->clearPendingLoginState();

                throw new TooManyMfaAttemptsException(
                    previous: $e,
                );
            }

            throw $e;
        }

        // A successful second factor resets the failure history.
        $this->mfaRateLimiter->clear(
            $authentication->userid,
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

    private function completeLogin(
        UserLoginContext|AnonymousLoginContext $context,
    ): Response {
        $rememberMe = $this->rememberMe->isRequested();

        new LoginHelper(
            $context,
            $this->Session,
            (int) $this->config['cookie_validity_time'],
        )->login($rememberMe);

        $this->clearPendingLoginState();
        $this->rememberMe->clear();
        $this->clearRedirectCookie();

        return new RedirectResponse(
            $this->getPostLoginRedirect($context),
        );
    }

    private function getPostLoginRedirect(
        UserLoginContext|AnonymousLoginContext $context,
    ): string {
        $default = '/index.php';

        // Preserve existing SAML behavior: elab_redirect is ignored.
        if (
            $context instanceof UserLoginContext
            && $context->authMethod === AuthMethod::Saml
        ) {
            return $default;
        }

        if (!$this->Request->cookies->has('elab_redirect')) {
            return $default;
        }

        $candidate = rawurldecode(
            $this->Request->cookies->getString(
                'elab_redirect',
                $default,
            ),
        );

        // Browsers strip TAB, CR and LF before parsing a URL, so a value such
        // as "/\t/evil.com" would become scheme-relative after these checks.
        $candidate = str_replace(array("\t", "\r", "\n"), '', $candidate);

        // Only allow local absolute paths.
        if (
            !str_starts_with($candidate, '/')
            || str_starts_with($candidate, '//')
            || str_contains($candidate, '\\')
        ) {
            return $default;
        }

        return $candidate;
    }

    private function clearRedirectCookie(): void
    {
        setcookie(
            'elab_redirect',
            '',
            array(
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ),
        );
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
        if (($this->config['local_auth_enabled'] ?? '0') !== '1') {
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
            $this->Request->request->getString(
                'teaminit_firstname',
            ),
            $this->Request->request->getString(
                'teaminit_lastname',
            ),
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
        if ($this->config['saml_toggle'] !== '1') {
            throw new UnauthorizedException();
        }
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

        // get expected request and idp from cookie
        $expectedRequestId = $this->samlRequestState->getRequestId();
        $expectedIdpId = $this->samlRequestState->getIdpId();
        if ($idpId !== $expectedIdpId) {
            throw new UnauthorizedException();
        }

        $authenticator = new SamlAuth(
            new SamlAuthLib($settings),
            $this->config,
            $settings,
        );

        $result = $authenticator->assertIdpResponse($expectedRequestId);

        $this->samlRequestState->clear();
        $this->clearPendingLoginState();

        if ($result instanceof InitialTeamSelectionRequired) {
            $this->storeInitialTeamSelection($result);
            return $this->samlRedirect('/login.php');
        }

        $this->setSamlToken(
            $authenticator->encodeToken($idpId),
            $this->rememberMe->isRequested(),
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
        if (
            !$this->Session->has('auth_userid')
            || !$this->Session->has('auth_method')
        ) {
            throw new UnauthorizedException();
        }

        $authMethod = AuthMethod::tryFrom(
            $this->Session->get('auth_method'),
        );

        if ($authMethod === null) {
            throw new UnauthorizedException();
        }

        return new Authentication(
            $this->Session->get('auth_userid'),
            $authMethod,
        );
    }

    private function getLdapAuthenticator(): AuthenticatorInterface
    {
        if ($this->config['ldap_toggle'] !== '1') {
            throw new UnauthorizedException();
        }
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
     * See https://owasp.org/www-community/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies
     */
    private function validateDeviceToken(): void
    {
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

    private function setSamlToken(
        string $token,
        bool $rememberMe,
    ): void {
        $cookieOptions = array(
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        );

        $sessionOptions = session_get_cookie_params();

        if ($rememberMe) {
            $cookieOptions['expires'] = time()
                + 60 * (int) $this->config['cookie_validity_time'];
        } elseif ($sessionOptions['lifetime'] > 0) {
            $cookieOptions['expires'] = time()
                + $sessionOptions['lifetime'];
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

        $this->clearPendingLoginState();

        $this->rememberMe->capture();

        $idpsHelper = new IdpsHelper(
            Config::getConfig(),
            new Idps(new Users()),
        );

        $idpId = $this->Request->request->getInt('idpId');

        $settings = $idpsHelper->getSettings($idpId);

        $returnUrl = $settings['baseurl']
            . '/index.php?acs';

        $saml = new SamlAuthLib($settings);

        $redirectUrl = $saml->login(
            $returnUrl,
            stay: true,
        );

        $this->samlRequestState->store(
            $saml->getLastRequestID(),
            $idpId,
        );

        return new RedirectResponse($redirectUrl);
    }
}

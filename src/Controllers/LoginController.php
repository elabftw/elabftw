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

use Elabftw\Auth\Anon;
use Elabftw\Auth\Cookie;
use Elabftw\Auth\CookieToken;
use Elabftw\Auth\Demo;
use Elabftw\Auth\External;
use Elabftw\Auth\Ldap;
use Elabftw\Auth\Local;
use Elabftw\Auth\Mfa;
use Elabftw\Auth\MfaGate;
use Elabftw\Auth\None;
use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Auth\Team;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\IdpsHelper;
use Elabftw\Enums\AuthType;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Enums\Entrypoint;
use Elabftw\Enums\Language;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Idps;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users2Teams;
use Elabftw\Params\UserParams;
use Elabftw\Services\DeviceToken;
use Elabftw\Services\DeviceTokenValidator;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\ResetPasswordKey;
use Elabftw\Services\TeamFinder;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

use function rawurldecode;
use function setcookie;
use function str_starts_with;

/**
 * For all your authentication/login needs
 */
final class LoginController implements ControllerInterface
{
    public function __construct(
        private readonly array $config,
        private readonly Request $Request,
        private readonly FlashBagAwareSessionInterface $Session,
        private readonly bool $demoMode = false,
    ) {}

    public function getAuthResponse(): AuthResponseInterface
    {
        // try to login with the cookie if we have one in the request
        // but don't let the exception bubble up if the cookie is invalid
        try {
            if ($this->Request->cookies->has('token')) {
                return new Cookie(
                    (int) $this->config['cookie_validity_time'],
                    new CookieToken($this->Request->cookies->getString('token')),
                    $this->Request->cookies->getInt('token_team'),
                )->tryAuth();
            }
        } catch (UnauthorizedException | IllegalActionException) {
        }

        return $this->getAuthService()->tryAuth();
    }

    #[Override]
    public function getResponse(): Response
    {
        // store the rememberme choice in a cookie, not the session as it won't follow up for saml
        $icanhazcookies = '0';
        if ($this->Request->request->has('rememberme') && $this->config['remember_me_allowed'] === '1') {
            $icanhazcookies = '1';
        }
        $cookieOptions = array(
            'expires' => time() + 300,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        setcookie('icanhazcookies', $icanhazcookies, $cookieOptions);

        // Get an AuthResponse from an AuthService
        $AuthResponse = $this->getAuthResponse();

        // user does not exist and no team was found so user must select one
        $info = $AuthResponse->getInitTeamInfo();
        if ($AuthResponse->initTeamRequired()) {
            $this->Session->set('initial_team_selection_required', true);
            $this->Session->set('teaminit_email', $info['email']);
            $this->Session->set('teaminit_firstname', $info['firstname']);
            $this->Session->set('teaminit_lastname', $info['lastname']);
            $this->Session->set('teaminit_orgid', $info['orgid'] ?? '');
            return new RedirectResponse('/login.php');
        }

        // First part of login is done, so we have a userid.
        // Next, we need to do other steps (possibly), before the full login in app
        $loggingInUser = $AuthResponse->getUser();

        // if we're receiving mfa_secret, it's because we just enabled MFA, so save it for that user
        if ($this->Request->request->has('mfa_secret') && $loggingInUser->userData['mfa_secret'] === null) {
            $loggingInUser->update(new UserParams('mfa_secret', $this->Request->request->getString('mfa_secret')));
        }

        /////////
        // MFA
        // check if we need to do mfa auth too after a first successful authentication
        $enforceMfa = EnforceMfa::from((int) $this->config['enforce_mfa']);
        // MFA can be required because the user has mfa_secret or because it is enforced for their level
        // we also track in the session if mfa has been verified because we might have other screens such as team select
        if (MfaGate::isMfaRequired($enforceMfa, $loggingInUser) && !$this->Session->has('has_verified_mfa')) {
            if ($AuthResponse->hasVerifiedMfa()) {
                $this->Session->remove('mfa_auth_required');
                $this->Session->remove('mfa_secret');
                $this->Session->set('has_verified_mfa', true);
            } else {
                $this->Session->set('mfa_auth_required', true);
                // remember which user is authenticated in the Session
                $this->Session->set('auth_userid', $AuthResponse->getAuthUserid());
                return new RedirectResponse('/login.php');
            }
        }

        /////////////////////
        // RENEW PASSWORD //
        // check if we need to renew our local password
        if ($AuthResponse->mustRenewPassword()) {
            // remember which user is authenticated
            $this->Session->set('auth_userid', $AuthResponse->getAuthUserid());
            $this->Session->set('renew_password_required', true);
            $ResetPasswordKey = new ResetPasswordKey(time(), Env::asString('SECRET_KEY'));
            $key = $ResetPasswordKey->generate($loggingInUser->userData['email']);
            return new RedirectResponse('/change-pass.php?key=' . $key);
        }

        ////////////////////
        // TEAM SELECTION //
        // if the user is in several teams, we need to redirect to the team selection
        if ($AuthResponse->isInSeveralTeams()) {
            $this->Session->set('team_selection_required', true);
            $this->Session->set('team_selection', $AuthResponse->getSelectableTeams());
            $this->Session->set('auth_userid', $AuthResponse->getAuthUserid());
            return new RedirectResponse('/login.php');
        }

        // user exists but no team was found so user must select one
        if ($AuthResponse->teamRequestSelectionRequired()) {
            $this->Session->set('team_request_selection_required', true);
            $this->Session->set('teaminit_userid', $info['userid']);
            return new RedirectResponse('/login.php');
        }

        // send a helpful message if account requires validation, needs to be after team selection
        if ($loggingInUser->userData['validated'] === 0) {
            throw new ImproperActionException(_('Your account is not validated. An admin of your team needs to validate it!'));
        }

        // All good now we can login the user
        $LoginHelper = new LoginHelper($AuthResponse, $this->Session, (int) $this->config['cookie_validity_time']);
        $LoginHelper->login((bool) $icanhazcookies);

        // cleanup
        $this->Session->remove('auth_userid');

        // we redirect to index that will then redirect to the correct entrypoint set by user
        $location = '/index.php';
        if ($this->Request->cookies->has('elab_redirect')) {
            // make sure we have a relative path
            $candidate = rawurldecode($this->Request->cookies->getString('elab_redirect', $location));
            if (str_starts_with($candidate, '/') && !str_starts_with($candidate, '//')) {
                $location = $candidate;
            }
        }
        return new RedirectResponse($location);
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
        // a devicetoken cookie might or might not exist, so this can be null
        $token = $this->Request->cookies->getString('devicetoken');
        // if a token is sent, we need to validate it
        $DeviceTokenValidator = new DeviceTokenValidator(DeviceToken::getConfig(), $token);
        $isTokenValid = $DeviceTokenValidator->validate();
        // if the token is not valid, verify we can login from untrusted devices for that user
        if ($isTokenValid === false) {
            // email might be for non existing user, which will throw exception
            try {
                $Users = ExistingUser::fromEmail($this->Request->request->getString('email'));
            } catch (ResourceNotFoundException) {
                throw new InvalidCredentialsException();
            }
            // check if authentication is locked for untrusted clients for that user
            if ($Users->allowUntrustedLogin() === false) {
                // reject any attempt whatsoever if this account is locked for untrusted devices
                throw new InvalidDeviceTokenException();
            }
        }
    }

    private function getAuthService(): AuthInterface
    {
        if ($this->Request->request->get('Cancel') === 'cancel') {
            return new None($this->Session);
        }
        // try to login with the elabid for an entity in view mode
        $entrypoint = basename($this->Request->getScriptName());
        if ($this->Request->query->has('access_key')
            && ($entrypoint === Entrypoint::Experiments->toPage() || $entrypoint === Entrypoint::Database->toPage())
            && $this->Request->query->get('mode') === 'view') {
            // ACCESS KEY
            // now we need to know in which team we autologin the user
            $TeamFinder = new TeamFinder(basename($this->Request->getScriptName()), $this->Request->query->getString('access_key'));
            $team = $TeamFinder->findTeam();

            if ($team === 0) {
                throw new UnauthorizedException();
            }
            return new Anon((bool) $this->config['anon_users'], $team, Language::EnglishGB);
        }

        // now the other types of Auth like Local, Ldap, Saml, etc...
        $authType = AuthType::tryFrom($this->Request->request->getAlpha('auth_type'));
        switch ($authType) {
            // AUTH WITH DEMO USER
            case AuthType::Demo:
                if (!$this->demoMode) {
                    throw new ImproperActionException('This instance is not in demo mode. Set DEMO_MODE=true to allow demo mode login.');
                }
                return new Demo($this->Request->request->getString('email'));

                // AUTH WITH LDAP
            case AuthType::Ldap:
                $this->Session->set('auth_service', AuthType::Ldap->asService());
                $c = $this->config;
                $ldapPassword = null;
                if (!empty($c['ldap_password'])) {
                    $ldapPassword = $c['ldap_password'];
                }
                $ldapConfig = array(
                    'protocol' => $c['ldap_scheme'] . '://',
                    'hosts' => explode(',', $c['ldap_host']),
                    'port' => (int) $c['ldap_port'],
                    'base_dn' => $c['ldap_base_dn'],
                    'username' => $c['ldap_username'],
                    'password' => $ldapPassword,
                    'use_tls' => (bool) $c['ldap_use_tls'],
                );
                $connection = new Connection($ldapConfig);
                // use a generic Entry object https://ldaprecord.com/docs/core/v2/models/#entry-model
                return new Ldap(
                    $connection,
                    new Entry(),
                    $c,
                    $this->Request->request->getString('email'),
                    $this->Request->request->getString('password')
                );

                // AUTH WITH LOCAL DATABASE
            case AuthType::Local:
                // make sure local auth is enabled
                if ($this->config['local_auth_enabled'] === '0') {
                    throw new ImproperActionException('Local authentication is disabled on this instance.');
                }
                // only local auth validates device token
                $this->validateDeviceToken();
                $this->Session->set('auth_service', AuthType::Local->asService());
                return new Local(
                    $this->Request->request->getString('email'),
                    $this->Request->request->getString('password'),
                    (bool) $this->config['local_login'],
                    (bool) $this->config['local_login_hidden_only_sysadmin'],
                    (bool) $this->config['local_login_only_sysadmin'],
                    (int) $this->config['max_password_age_days'],
                    (int) $this->config['login_tries'],
                );

                // AUTH WITH SAML
            case AuthType::Saml:
                $this->Session->set('auth_service', AuthType::Saml->asService());
                $IdpsHelper = new IdpsHelper(Config::getConfig(), new Idps(new Users()));
                $idpId = $this->Request->request->getInt('idpId');
                // No cookie is required anymore, as entity Id is extracted from response
                $settings = $IdpsHelper->getSettings($idpId);
                return new SamlAuth(new SamlAuthLib($settings), $this->config, $settings);

            case AuthType::External:
                $this->Session->set('auth_service', AuthType::External->asService());
                return new External(
                    $this->config,
                    $this->Request->server->all(),
                );

                // AUTH AS ANONYMOUS USER
            case AuthType::Anonymous:
                return new Anon((bool) $this->config['anon_users'], $this->Request->request->getInt('team_id'), Language::EnglishGB);

                // AUTH in a team (after the team selection page)
                // we are already authenticated
            case AuthType::Team:
                return new Team(
                    $this->Session->get('auth_userid'),
                    $this->Request->request->getInt('selected_team'),
                );

                // MFA AUTH
            case AuthType::Mfa:
                return new Mfa(
                    new MfaHelper($this->Session->get('mfa_secret') ?? $this->Request->request->get('mfa_secret')),
                    $this->Session->get('auth_userid'),
                    $this->Request->request->getAlnum('mfa_code'),
                );
            case AuthType::TeamInit:
                $this->initTeamSelection();
                exit;
            case AuthType::TeamSelection:
                $this->teamSelection($this->Session->get('teaminit_userid'), $this->Request->request->getInt('team_id'));
                exit;

            default:
                throw new UnauthorizedException();
        }
    }

    /**
     * For when a user already exists but has no associated team
     */
    private function teamSelection(int $userid, int $teamId): void
    {
        // Ensure that the team is actually one that users should be able to select.
        $TeamsHelper = new TeamsHelper($teamId);
        $TeamsHelper->teamIsVisibleOrExplode();

        $this->Session->remove('team_selection_required');
        $Users2Teams = new Users2Teams(new Users($userid));
        $Users2Teams->create($userid, $teamId);
        $this->Session->remove('teaminit_userid');
        $this->Session->remove('team_request_selection_required');
        // TODO avoid re-login
        $this->Session->getFlashBag()->add('ok', _('Your account has been associated successfully to a team. Please authenticate again.'));
        $location = '/login.php';
        echo "<html><head><meta http-equiv='refresh' content='1;url=$location' /><title>You are being redirected...</title></head><body>You are being redirected...</body></html>";
    }

    private function initTeamSelection(): void
    {
        // Ensure that the team is actually one that users should be able to select.
        $TeamsHelper = new TeamsHelper($this->Request->request->getInt('team_id'));
        $TeamsHelper->teamIsVisibleOrExplode();

        // create a user in the requested team
        $newUser = ExistingUser::fromScratch(
            $this->Session->get('teaminit_email'),
            array($this->Request->request->getInt('team_id')),
            $this->Request->request->getString('teaminit_firstname'),
            $this->Request->request->getString('teaminit_lastname'),
            orgid: $this->Session->get('teaminit_orgid'),
        );
        $this->Session->set('teaminit_done', true);
        // will display the appropriate message to user
        $this->Session->set('teaminit_done_need_validation', (string) $newUser->needValidation);
        $this->Session->remove('initial_team_selection_required');
        $location = '/login.php';
        echo "<html><head><meta http-equiv='refresh' content='1;url=$location' /><title>You are being redirected...</title></head><body>You are being redirected...</body></html>";
    }
}

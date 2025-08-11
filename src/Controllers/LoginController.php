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

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Auth\Anon;
use Elabftw\Auth\Demo;
use Elabftw\Auth\External;
use Elabftw\Auth\Ldap;
use Elabftw\Auth\Local;
use Elabftw\Auth\Mfa;
use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Auth\Team;
use Elabftw\Elabftw\Env;
use Elabftw\Elabftw\IdpsHelper;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users\ExistingUser;
use Elabftw\Models\Idps;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users2Teams;
use Elabftw\Services\DeviceToken;
use Elabftw\Services\DeviceTokenValidator;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\TeamsHelper;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\ResetPasswordKey;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use Monolog\Logger;
use OneLogin\Saml2\Auth as SamlAuthLib;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

use function setcookie;

/**
 * For all your authentication/login needs
 */
final class LoginController implements ControllerInterface
{
    public const int AUTH_DEMO = 5;

    public const int AUTH_LOCAL = 10;

    public const int AUTH_SAML = 20;

    public const int AUTH_LDAP = 30;

    public const int AUTH_EXTERNAL = 40;

    public const int AUTH_ANON = 50;

    public function __construct(
        private readonly Config $Config,
        private readonly Request $Request,
        private readonly FlashBagAwareSessionInterface $Session,
        private readonly Logger $Logger,
        private readonly Users $Users,
        private readonly bool $demoMode = false,
    ) {}

    #[Override]
    public function getResponse(): Response
    {
        // ENABLE MFA FOR OUR USER
        if ($this->Session->has('enable_mfa')) {
            $location = $this->enableMFA();
            if ($location !== '') {
                return new RedirectResponse($location);
            }
        }

        // get our Auth service
        $authType = $this->Request->request->getAlpha('auth_type');

        // store the rememberme choice in a cookie, not the session as it won't follow up for saml
        $icanhazcookies = '0';
        if ($this->Request->request->has('rememberme') && $this->Config->configArr['remember_me_allowed'] === '1') {
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

        // TRY TO AUTHENTICATE
        $AuthResponse = $this->getAuthService($authType)->tryAuth();

        if ($this->Session->get('mfa_auth_required') === true && !$AuthResponse->hasVerifiedMfa) {
            throw new IllegalActionException('MFA auth is required');
        }

        /////////////////
        // ENFORCE MFA //
        /////////////////
        // If MFA is enforced by Sysadmin (only for local auth) the user has to set it up
        if ($authType === 'local'
            && Local::enforceMfa(
                $AuthResponse,
                (int) $this->Config->configArr['enforce_mfa']
            )
        ) {
            // Need to request verification code to confirm user got secret and can authenticate in the future by MFA
            // so we will require mfa, redirect the user to login
            // which will pickup that enable_mfa is there so it will display the qr code to initialize the process
            // and after that we redirect back to login to cleanup
            // the mfa_secret is not yet saved to the DB
            $this->Session->set('enforce_mfa', true);
            $this->Session->set('enable_mfa', true);
            $this->Session->set('mfa_auth_required', true);
            $this->Session->set('mfa_secret', (new MfaHelper(0))->generateSecret());
            $this->Session->set('auth_userid', $AuthResponse->userid);
            $this->Session->set('rememberme', $icanhazcookies);

            return new RedirectResponse('/login.php');
        }

        /////////
        // MFA //
        /////////
        // check if we need to do mfa auth too after a first successful authentication
        if ($AuthResponse->mfaSecret && !$AuthResponse->hasVerifiedMfa) {
            $this->Session->set('mfa_auth_required', true);
            $this->Session->set('mfa_secret', $AuthResponse->mfaSecret);
            // remember which user is authenticated
            $this->Session->set('auth_userid', $AuthResponse->userid);
            $this->Session->set('rememberme', $icanhazcookies);
            return new RedirectResponse('/login.php');
        }
        if ($AuthResponse->hasVerifiedMfa) {
            $this->Session->remove('enforce_mfa');
            $this->Session->remove('mfa_auth_required');
            $this->Session->remove('mfa_secret');
        }

        /////////////////////
        // RENEW PASSWORD //
        ///////////////////
        // check if we need to renew our local password
        if ($AuthResponse->mustRenewPassword) {
            // remember which user is authenticated
            $this->Session->set('auth_userid', $AuthResponse->userid);
            $this->Session->set('rememberme', $icanhazcookies);
            $this->Session->set('renew_password_required', true);
            $ResetPasswordKey = new ResetPasswordKey(time(), Env::asString('SECRET_KEY'));
            $Users = new Users($this->Session->get('auth_userid'));
            $key = $ResetPasswordKey->generate($Users->userData['email']);
            return new RedirectResponse('/change-pass.php?key=' . $key);
        }

        ////////////////////
        // TEAM SELECTION //
        ////////////////////
        // if the user is in several teams, we need to redirect to the team selection
        if ($AuthResponse->isInSeveralTeams) {
            $this->Session->set('team_selection_required', true);
            $this->Session->set('team_selection', $AuthResponse->selectableTeams);
            $this->Session->set('auth_userid', $AuthResponse->userid);
            // carry over the cookie
            $this->Session->set('rememberme', $icanhazcookies);
            return new RedirectResponse('/login.php');
        }

        // user does not exist and no team was found so user must select one
        if ($AuthResponse->initTeamRequired) {
            $this->Session->set('initial_team_selection_required', true);
            $this->Session->set('teaminit_email', $AuthResponse->initTeamUserInfo['email']);
            $this->Session->set('teaminit_firstname', $AuthResponse->initTeamUserInfo['firstname']);
            $this->Session->set('teaminit_lastname', $AuthResponse->initTeamUserInfo['lastname']);
            $this->Session->set('teaminit_orgid', $AuthResponse->initTeamUserInfo['orgid'] ?? '');
            return new RedirectResponse('/login.php');
        }

        // user exists but no team was found so user must select one
        if ($AuthResponse->teamRequestSelectionRequired) {
            $this->Session->set('team_request_selection_required', true);
            $this->Session->set('teaminit_userid', $AuthResponse->initTeamUserInfo['userid']);
            return new RedirectResponse('/login.php');
        }

        // send a helpful message if account requires validation, needs to be after team selection
        if ($AuthResponse->isValidated === false) {
            throw new ImproperActionException(_('Your account is not validated. An admin of your team needs to validate it!'));
        }

        // All good now we can login the user
        $LoginHelper = new LoginHelper($AuthResponse, $this->Session);
        $LoginHelper->login((bool) $icanhazcookies);

        // cleanup
        $this->Session->remove('rememberme');
        $this->Session->remove('auth_userid');

        // we redirect to index that will then redirect to the correct entrypoint set by user
        return new RedirectResponse('/index.php');
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
                throw new QuantumException(_('Invalid email/password combination.'));
            }
            // check if authentication is locked for untrusted clients for that user
            if ($Users->allowUntrustedLogin() === false) {
                // reject any attempt whatsoever if this account is locked for untrusted devices
                throw new InvalidDeviceTokenException();
            }
        }
    }

    private function getAuthService(string $authType): AuthInterface
    {
        switch ($authType) {
            // AUTH WITH DEMO USER
            case 'demo':
                $this->Session->set('auth_service', self::AUTH_DEMO);
                if (!$this->demoMode) {
                    throw new ImproperActionException('This instance is not in demo mode. Set DEMO_MODE=true to allow demo mode login.');
                }
                return new Demo($this->Request->request->getString('email'));

                // AUTH WITH LDAP
            case 'ldap':
                $this->Session->set('auth_service', self::AUTH_LDAP);
                $c = $this->Config->configArr;
                $ldapPassword = null;
                // assume there is a password to decrypt if username is not null
                if ($c['ldap_username']) {
                    $ldapPassword = Crypto::decrypt($c['ldap_password'], Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
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
            case 'local':
                // make sure local auth is enabled
                if ($this->Config->configArr['local_auth_enabled'] === '0') {
                    throw new ImproperActionException('Local authentication is disabled on this instance.');
                }
                $this->Session->set('auth_service', self::AUTH_LOCAL);
                // only local auth validates device token
                $this->validateDeviceToken();
                return new Local(
                    $this->Request->request->getString('email'),
                    $this->Request->request->getString('password'),
                    (bool) $this->Config->configArr['local_login'],
                    (bool) $this->Config->configArr['local_login_hidden_only_sysadmin'],
                    (bool) $this->Config->configArr['local_login_only_sysadmin'],
                    (int) $this->Config->configArr['max_password_age_days'],
                    (int) $this->Config->configArr['login_tries'],
                );

                // AUTH WITH SAML
            case 'saml':
                $this->Session->set('auth_service', self::AUTH_SAML);
                $IdpsHelper = new IdpsHelper($this->Config, new Idps($this->Users));
                $idpId = $this->Request->request->getInt('idpId');
                // No cookie is required anymore, as entity Id is extracted from response
                $settings = $IdpsHelper->getSettings($idpId);
                return new SamlAuth(new SamlAuthLib($settings), $this->Config->configArr, $settings);

            case 'external':
                $this->Session->set('auth_service', self::AUTH_EXTERNAL);
                return new External(
                    $this->Config->configArr,
                    $this->Request->server->all(),
                    $this->Logger,
                );

                // AUTH AS ANONYMOUS USER
            case 'anon':
                $this->Session->set('auth_service', self::AUTH_ANON);
                return new Anon((bool) $this->Config->configArr['anon_users'], $this->Request->request->getInt('team_id'));

                // AUTH in a team (after the team selection page)
                // we are already authenticated
            case 'team':
                return new Team(
                    $this->Session->get('auth_userid'),
                    $this->Request->request->getInt('selected_team'),
                );

                // MFA AUTH
            case 'mfa':
                return new Mfa(
                    new MfaHelper(
                        $this->Session->get('auth_userid'),
                        $this->Session->get('mfa_secret'),
                    ),
                    $this->Request->request->getAlnum('mfa_code'),
                );
            case 'teaminit':
                $this->initTeamSelection();
                exit;
            case 'teamselection':
                $this->teamSelection($this->Session->get('teaminit_userid'), $this->Request->request->getInt('team_id'));
                exit;

            default:
                throw new ImproperActionException('Could not determine which authentication service to use.');
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

    private function enableMFA(): string
    {
        $flashBag = $this->Session->getFlashBag();

        if ($this->Request->request->get('Cancel') === 'cancel') {
            $this->Session->clear();
            $flashBag->add('ko', _('Two Factor Authentication was not enabled!'));
            return '/login.php';
        }

        $userid = $this->Users->userData['userid'] ?? $this->Session->get('auth_userid');
        $MfaHelper = new MfaHelper($userid, $this->Session->get('mfa_secret'));

        // check the input code against the secret stored in session
        if (!$MfaHelper->verifyCode($this->Request->request->getAlnum('mfa_code'))) {
            $flashBag->add('ko', _('The code you entered is not valid!'));
            return '/login.php';
        }

        // all good, save the secret in the database now that we now the user can authenticate against it
        $MfaHelper->saveSecret();
        $this->Session->remove('enable_mfa');

        $flashBag->add('ok', _('Two Factor Authentication is now enabled!'));

        $location = $this->Session->get('mfa_redirect_origin', '');

        if (!$this->Session->get('enforce_mfa')) {
            $this->Session->remove('mfa_auth_required');
            $this->Session->remove('mfa_secret');
            $this->Session->remove('mfa_redirect_origin');
        }

        return $location;
    }
}

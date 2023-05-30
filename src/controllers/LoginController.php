<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Auth\Anon;
use Elabftw\Auth\External;
use Elabftw\Auth\Ldap;
use Elabftw\Auth\Local;
use Elabftw\Auth\Mfa;
use Elabftw\Auth\Saml as SamlAuth;
use Elabftw\Auth\Team;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Saml;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Config;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Idps;
use Elabftw\Services\DeviceToken;
use Elabftw\Services\DeviceTokenValidator;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\MfaHelper;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;
use OneLogin\Saml2\Auth as SamlAuthLib;
use function setcookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * For all your authentication/login needs
 */
class LoginController implements ControllerInterface
{
    public const AUTH_LOCAL = 10;

    public const AUTH_SAML = 20;

    public const AUTH_LDAP = 30;

    public const AUTH_EXTERNAL = 40;

    public const AUTH_ANON = 50;

    public function __construct(private App $App)
    {
    }

    public function getResponse(): Response
    {
        // ENABLE MFA FOR OUR USER
        if ($this->App->Session->has('enable_mfa')) {
            $location = $this->enableMFA();
            if ($location !== '') {
                return new RedirectResponse($location);
            }
        }

        // get our Auth service
        $authType = $this->App->Request->request->getAlpha('auth_type');

        // store the rememberme choice in a cookie, not the session as it won't follow up for saml
        $icanhazcookies = '0';
        if ($this->App->Request->request->has('rememberme') && $this->App->Config->configArr['remember_me_allowed'] === '1') {
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

        // INITIAL TEAM SELECTION
        $this->initTeamSelection($authType);

        // TRY TO AUTHENTICATE
        $AuthResponse = $this->getAuthService($authType)->tryAuth();

        /////////////////
        // ENFORCE MFA //
        /////////////////
        // If MFA is enforced by Sysadmin (only for local auth) the user has to set it up
        if ($authType === 'local'
            && Local::enforceMfa(
                $AuthResponse,
                (int) $this->App->Config->configArr['enforce_mfa']
            )
        ) {
            // Need to request verification code to confirm user got secret and can authenticate in the future by MFA
            // so we will require mfa, redirect the user to login
            // which will pickup that enable_mfa is there so it will display the qr code to initialize the process
            // and after that we redirect back to login to cleanup
            // the mfa_secret is not yet saved to the DB
            $this->App->Session->set('enforce_mfa', true);
            $this->App->Session->set('enable_mfa', true);
            $this->App->Session->set('mfa_auth_required', true);
            $this->App->Session->set('mfa_secret', (new MfaHelper(0))->generateSecret());
            $this->App->Session->set('auth_userid', $AuthResponse->userid);
            $this->App->Session->set('rememberme', $icanhazcookies);

            return new RedirectResponse('../../login.php');
        }

        /////////
        // MFA //
        /////////
        // check if we need to do mfa auth too after a first successful authentication
        if ($AuthResponse->mfaSecret && !$AuthResponse->hasVerifiedMfa) {
            $this->App->Session->set('mfa_auth_required', true);
            $this->App->Session->set('mfa_secret', $AuthResponse->mfaSecret);
            // remember which user is authenticated
            $this->App->Session->set('auth_userid', $AuthResponse->userid);
            $this->App->Session->set('rememberme', $icanhazcookies);
            return new RedirectResponse('../../login.php');
        }
        if ($AuthResponse->hasVerifiedMfa) {
            $this->App->Session->remove('enforce_mfa');
            $this->App->Session->remove('mfa_auth_required');
            $this->App->Session->remove('mfa_secret');
        }

        ////////////////////
        // TEAM SELECTION //
        ////////////////////
        // if the user is in several teams, we need to redirect to the team selection
        if ($AuthResponse->isInSeveralTeams) {
            $this->App->Session->set('team_selection_required', true);
            $this->App->Session->set('team_selection', $AuthResponse->selectableTeams);
            $this->App->Session->set('auth_userid', $AuthResponse->userid);
            // carry over the cookie
            $this->App->Session->set('rememberme', $icanhazcookies);
            return new RedirectResponse('../../login.php');
        }

        // no team was found so user must select one
        if ($AuthResponse->initTeamRequired) {
            $this->App->Session->set('initial_team_selection_required', true);
            $this->App->Session->set('teaminit_email', $AuthResponse->initTeamUserInfo['email']);
            $this->App->Session->set('teaminit_firstname', $AuthResponse->initTeamUserInfo['firstname']);
            $this->App->Session->set('teaminit_lastname', $AuthResponse->initTeamUserInfo['lastname']);
            return new RedirectResponse('../../login.php');
        }
        // All good now we can login the user
        $LoginHelper = new LoginHelper($AuthResponse, $this->App->Session);
        $LoginHelper->login((bool) $icanhazcookies);

        // cleanup
        $this->App->Session->remove('rememberme');
        $this->App->Session->remove('auth_userid');

        return new RedirectResponse(
            (string) ($this->App->Request->cookies->get('elab_redirect') ?? '../../experiments.php')
        );
    }

    /**
     * See https://owasp.org/www-community/Slow_Down_Online_Guessing_Attacks_with_Device_Cookies
     */
    private function validateDeviceToken(): void
    {
        // skip for multi team auth
        if ($this->App->Session->has('auth_userid')) {
            return;
        }
        // a devicetoken cookie might or might not exist, so this can be null
        $token = (string) $this->App->Request->cookies->get('devicetoken');
        // if a token is sent, we need to validate it
        $DeviceTokenValidator = new DeviceTokenValidator(DeviceToken::getConfig(), $token);
        $isTokenValid = $DeviceTokenValidator->validate();
        // if the token is not valid, verify we can login from untrusted devices for that user
        if ($isTokenValid === false) {
            // email might be for non existing user, which will throw exception
            try {
                $Users = ExistingUser::fromEmail((string) $this->App->Request->request->get('email'));
            } catch (ResourceNotFoundException) {
                throw new InvalidDeviceTokenException();
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
            // AUTH WITH LDAP
            case 'ldap':
                $this->App->Session->set('auth_service', self::AUTH_LDAP);
                $c = $this->App->Config->configArr;
                $ldapPassword = null;
                // assume there is a password to decrypt if username is not null
                if ($c['ldap_username']) {
                    $ldapPassword = Crypto::decrypt($c['ldap_password'], Key::loadFromAsciiSafeString(Config::fromEnv('SECRET_KEY')));
                }
                $ldapConfig = array(
                    'hosts' => array($c['ldap_host']),
                    'port' => (int) $c['ldap_port'],
                    'base_dn' => $c['ldap_base_dn'],
                    'username' => $c['ldap_username'],
                    'password' => $ldapPassword,
                    'use_tls' => (bool) $c['ldap_use_tls'],
                );
                $connection = new Connection($ldapConfig);
                // use a generic Entry object https://ldaprecord.com/docs/core/v2/models/#entry-model
                return new Ldap($connection, new Entry(), $c, (string) $this->App->Request->request->get('email'), (string) $this->App->Request->request->get('password'));

                // AUTH WITH LOCAL DATABASE
            case 'local':
                $this->App->Session->set('auth_service', self::AUTH_LOCAL);
                // only local auth validates device token
                $this->validateDeviceToken();
                return new Local((string) $this->App->Request->request->get('email'), (string) $this->App->Request->request->get('password'));

                // AUTH WITH SAML
            case 'saml':
                $this->App->Session->set('auth_service', self::AUTH_SAML);
                $Saml = new Saml($this->App->Config, new Idps());
                $idpId = (int) $this->App->Request->request->get('idpId');
                // No cookie is required anymore, as entity Id is extracted from response
                $settings = $Saml->getSettings($idpId);
                return new SamlAuth(new SamlAuthLib($settings), $this->App->Config->configArr, $settings);

            case 'external':
                $this->App->Session->set('auth_service', self::AUTH_EXTERNAL);
                return new External(
                    $this->App->Config->configArr,
                    $this->App->Request->server->all(),
                    $this->App->Log,
                );

                // AUTH AS ANONYMOUS USER
            case 'anon':
                $this->App->Session->set('auth_service', self::AUTH_ANON);
                return new Anon($this->App->Config->configArr, (int) $this->App->Request->request->get('team_id'));

                // AUTH in a team (after the team selection page)
                // we are already authenticated
            case 'team':
                return new Team(
                    $this->App->Session->get('auth_userid'),
                    (int) $this->App->Request->request->get('selected_team'),
                );

                // MFA AUTH
            case 'mfa':
                return new Mfa(
                    new MfaHelper(
                        (int) $this->App->Session->get('auth_userid'),
                        $this->App->Session->get('mfa_secret'),
                    ),
                    $this->App->Request->request->getAlnum('mfa_code'),
                );

            default:
                throw new ImproperActionException('Could not determine which authentication service to use.');
        }
    }

    private function initTeamSelection(string $authType): void
    {
        if ($authType === 'teaminit'
            && $this->App->Session->get('initial_team_selection_required')
        ) {
            // create a user in the requested team
            $newUser = ExistingUser::fromScratch(
                $this->App->Session->get('teaminit_email'),
                array((int) $this->App->Request->request->get('team_id')),
                (string) $this->App->Request->request->get('teaminit_firstname'),
                (string) $this->App->Request->request->get('teaminit_lastname'),
            );
            $this->App->Session->set('teaminit_done', true);
            // will display the appropriate message to user
            $this->App->Session->set('teaminit_done_need_validation', (string) $newUser->needValidation);
            $this->App->Session->remove('initial_team_selection_required');
            $location = '../../login.php';
            echo "<html><head><meta http-equiv='refresh' content='1;url=$location' /><title>You are being redirected...</title></head><body>You are being redirected...</body></html>";
            exit;
        }
    }

    private function enableMFA(): string
    {
        $flashBag = $this->App->Session->getBag('flashes');
        $flashKey = 'ko';
        $flashValue = _('Two Factor Authentication was not enabled!');

        // Only save if user didn't click Cancel button
        if ($this->App->Request->request->get('Submit') === 'submit') {
            $userid = isset($this->App->Users->userData['userid'])
                ? (int) $this->App->Users->userData['userid']
                : $this->App->Session->get('auth_userid');
            $MfaHelper = new MfaHelper($userid, $this->App->Session->get('mfa_secret'));

            // check the input code against the secret stored in session
            if (!$MfaHelper->verifyCode($this->App->Request->request->getAlnum('mfa_code'))) {
                if ($flashBag instanceof FlashBag) {
                    $flashBag->add($flashKey, _('The code you entered is not valid!'));
                }
                return '../../login.php';
            }

            // all good, save the secret in the database now that we now the user can authenticate against it
            $MfaHelper->saveSecret();
            $flashKey = 'ok';
            $flashValue = _('Two Factor Authentication is now enabled!');
            $this->App->Session->remove('enable_mfa');
        }

        if ($flashBag instanceof FlashBag) {
            $flashBag->add($flashKey, $flashValue);
        }

        $location = $this->App->Session->get('mfa_redirect_origin', '');

        if (!$this->App->Session->get('enforce_mfa')) {
            $this->App->Session->remove('mfa_auth_required');
            $this->App->Session->remove('mfa_secret');
            $this->App->Session->remove('mfa_redirect_origin');
        }

        return $location;
    }
}

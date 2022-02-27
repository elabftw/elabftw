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
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Saml;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidDeviceTokenException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Idps;
use Elabftw\Services\AnonAuth;
use Elabftw\Services\DeviceToken;
use Elabftw\Services\DeviceTokenValidator;
use Elabftw\Services\ExternalAuth;
use Elabftw\Services\LdapAuth;
use Elabftw\Services\LocalAuth;
use Elabftw\Services\LoginHelper;
use Elabftw\Services\MfaAuth;
use Elabftw\Services\MfaHelper;
use Elabftw\Services\SamlAuth;
use Elabftw\Services\TeamAuth;
use LdapRecord\Connection;
use OneLogin\Saml2\Auth as SamlAuthLib;
use const SECRET_KEY;
use function setcookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * For all your authentication/login needs
 */
class LoginController implements ControllerInterface
{
    public function __construct(private App $App)
    {
    }

    public function getResponse(): Response
    {
        // ENABLE MFA FOR OUR USER
        if ($this->App->Session->has('enable_mfa')) {
            $flashBag = $this->App->Session->getBag('flashes');
            $flashKey = 'ko';
            $flashValue = _('Two Factor Authentication was not enabled!');

            // Only save if user didn't click Cancel button
            if ($this->App->Request->request->get('Submit') === 'submit') {
                $MfaHelper = new MfaHelper(
                    (int) $this->App->Users->userData['userid'],
                    $this->App->Session->get('mfa_secret'),
                );

                // check the input code against the secret stored in session
                if (!$MfaHelper->verifyCode($this->App->Request->request->getAlnum('mfa_code'))) {
                    if ($flashBag instanceof FlashBag) {
                        $flashBag->add($flashKey, _('The code you entered is not valid!'));
                    }
                    return new RedirectResponse('../../login.php');
                }

                // all good, save the secret in the database now that we now the user can authenticate against it
                $MfaHelper->saveSecret();
                $flashKey = 'ok';
                $flashValue = _('Two Factor Authentication is now enabled!');
            }

            if ($flashBag instanceof FlashBag) {
                $flashBag->add($flashKey, $flashValue);
            }

            $this->App->Session->remove('enable_mfa');
            $this->App->Session->remove('mfa_auth_required');
            $this->App->Session->remove('mfa_secret');

            return new RedirectResponse('../../ucp.php?tab=2');
        }

        // get our Auth service
        $authType = $this->App->Request->request->getAlpha('auth_type');

        // store the rememberme choice in a cookie, not the session as it won't follow up for saml
        $icanhazcookies = '0';
        if ($this->App->Request->request->has('rememberme')) {
            $icanhazcookies = '1';
        }
        $cookieOptions = array(
            'expires' => time() + 300,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        );
        setcookie('icanhazcookies', $icanhazcookies, $cookieOptions);

        // try to authenticate
        $AuthResponse = $this->getAuthService($authType)->tryAuth();

        /////////
        // MFA //
        /////////
        // check if we need to do mfa auth too after a first successful authentication
        if ($AuthResponse->mfaSecret && !$AuthResponse->hasVerifiedMfa) {
            $this->App->Session->set('mfa_auth_required', true);
            $this->App->Session->set('mfa_secret', $AuthResponse->mfaSecret);
            // remember which user is authenticated
            $this->App->Session->set('auth_userid', $AuthResponse->userid);
            return new RedirectResponse('../../login.php');
        }
        if ($AuthResponse->hasVerifiedMfa) {
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
        $isTokenValid = false;
        // a devicetoken cookie might or might not exist, so this can be null
        $token = $this->App->Request->cookies->get('devicetoken');
        // if a token is sent, we need to validate it
        if (is_string($token)) {
            $DeviceTokenValidator = new DeviceTokenValidator(DeviceToken::getConfig(), $token);
            $isTokenValid = $DeviceTokenValidator->validate();
        }
        // if the token is not valid, verify we can login from untrusted devices for that user
        if ($isTokenValid === false) {
            // email might be for non existing user, which will throw exception
            try {
                $Users = ExistingUser::fromEmail((string) $this->App->Request->request->get('email'));
            } catch (ResourceNotFoundException $e) {
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
                $c = $this->App->Config->configArr;
                $ldapPassword = null;
                // assume there is a password to decrypt if username is not null
                if ($c['ldap_username']) {
                    $ldapPassword = Crypto::decrypt($c['ldap_password'], Key::loadFromAsciiSafeString(SECRET_KEY));
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
                return new LdapAuth($connection, $c, (string) $this->App->Request->request->get('email'), (string) $this->App->Request->request->get('password'));

            // AUTH WITH LOCAL DATABASE
            case 'local':
                // only local auth validates device token
                $this->validateDeviceToken();
                return new LocalAuth((string) $this->App->Request->request->get('email'), (string) $this->App->Request->request->get('password'));

            // AUTH WITH SAML
            case 'saml':
                $Saml = new Saml($this->App->Config, new Idps());
                $idpId = (int) $this->App->Request->request->get('idpId');
                // set a cookie to remember the idpid, used later on the assertion step
                $cookieOptions = array(
                    'expires' => time() + 300,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    // IMPORTANT: because we get redirected from IDP, SameSite attribute has to be None here!
                    // otherwise cookies won't be sent and we won't be able to know for which IDP we assert the response
                    // during the second part of the auth
                    'samesite' => 'None',
                );
                setcookie('idp_id', (string) $idpId, $cookieOptions);
                $settings = $Saml->getSettings($idpId);
                return new SamlAuth(new SamlAuthLib($settings), $this->App->Config->configArr, $settings);

            case 'external':
                return new ExternalAuth(
                    $this->App->Config->configArr,
                    $this->App->Request->server->all(),
                    $this->App->Log,
                );

            // AUTH AS ANONYMOUS USER
            case 'anon':
                return new AnonAuth($this->App->Config->configArr, (int) $this->App->Request->request->get('team_id'));

            // AUTH in a team (after the team selection page)
            // we are already authenticated
            case 'team':
                return new TeamAuth(
                    $this->App->Session->get('auth_userid'),
                    (int) $this->App->Request->request->get('selected_team'),
                );

            // MFA AUTH
            case 'mfa':
                return new MfaAuth(
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
}

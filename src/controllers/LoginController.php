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
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Idps;
use Elabftw\Services\AnonAuth;
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
use function setcookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * For all your authentication/login needs
 */
class LoginController implements ControllerInterface
{
    private App $App;

    public function __construct(App $app)
    {
        $this->App = $app;
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
                if (!$MfaHelper->verifyCode($this->App->Request->request->get('mfa_code') ?? '')) {
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

        // store the rememberme choice in session
        $this->App->Session->set('rememberme', false);
        if ($this->App->Request->request->has('rememberme')) {
            $this->App->Session->set('rememberme', true);
        }


        // get our Auth service and try to authenticate
        $authType = $this->App->Request->request->get('auth_type');
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
        $LoginHelper->login($this->App->Session->get('rememberme'));

        // cleanup
        $this->App->Session->remove('failed_attempt');
        $this->App->Session->remove('rememberme');
        $this->App->Session->remove('auth_userid');

        return new RedirectResponse(
            $this->App->Request->cookies->get('redirect') ?? '../../experiments.php'
        );
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
                    $ldapPassword = Crypto::decrypt($c['ldap_password'], Key::loadFromAsciiSafeString(\SECRET_KEY));
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
                return new LdapAuth($connection, $c, $this->App->Request->request->get('email'), $this->App->Request->request->get('password'));

            // AUTH WITH LOCAL DATABASE
            case 'local':
                return new LocalAuth($this->App->Request->request->get('email'), $this->App->Request->request->get('password'));

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
                    $this->App->Request->request->get('mfa_code') ?? '',
                );

            default:
                throw new ImproperActionException('Could not determine which authentication service to use.');
        }
    }
}

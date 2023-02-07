<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Controllers\LoginController;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Enums\EnforceMfa;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * Methods regarding Mfa during login
 */
class LoginMfaHelper
{
    public function __construct(private App $App)
    {
    }

    public function enableMFA(): string
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
        }

        if ($flashBag instanceof FlashBag) {
            $flashBag->add($flashKey, $flashValue);
        }

        $location = $this->App->Session->get('mfa_redirect_origin', '');

        if (!$this->App->Session->get('enforce_mfa')) {
            $this->App->Session->remove('enable_mfa');
            $this->App->Session->remove('mfa_auth_required');
            $this->App->Session->remove('mfa_secret');
            $this->App->Session->remove('mfa_redirect_origin');
        }

        return $location;
    }

    /**
     * Do we enfore MFA for this user? If yes, provide secret to user.
     */
    public function enforceMFA(AuthResponse $AuthResponse): bool
    {
        $EnforceMfaSetting = EnforceMfa::tryFrom((int) $this->App->Config->configArr['enforce_mfa']);
        // If MFA is enforced for local auth by Sysadmin the user has to set it up
        if ((int) $this->App->Session->get('auth_service') === LoginController::AUTH_LOCAL
            // Anon can still get access
            && !$this->App->Session->get('is_anon')
            // only if there is no secret stored
            && !$AuthResponse->mfaSecret
            // enforce for SysAdmins or Admins or Everyone?
            && (
                ($AuthResponse->isSysAdmin && $EnforceMfaSetting === EnforceMfa::SysAdmins)
                || ($AuthResponse->isAdmin && $EnforceMfaSetting === EnforceMfa::Admins)
                || $EnforceMfaSetting === EnforceMfa::Everyone
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

            return true;
        }

        return false;
    }
}

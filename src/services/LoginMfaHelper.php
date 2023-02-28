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
     * Do we enfore MFA for this user?
     */
    public static function enforceMfaForUser(AuthResponse $AuthResponse, int $enforceMfa): bool
    {
        // only if there is no secret stored; enforce for SysAdmins or Admins or Everyone?
        return (!$AuthResponse->mfaSecret
            && self::isMfaEnforcedForUser(
                $AuthResponse->isAdmin,
                $AuthResponse->isSysAdmin,
                $enforceMfa,
            )
        );
    }

    /**
     * Is Mfa enforced for a given user?
     */
    public static function isMfaEnforcedForUser(bool $isAdmin, bool $isSysAdmin, int $enforceMfa): bool
    {
        $EnforceMfaSetting = EnforceMfa::tryFrom($enforceMfa);
        return ($isSysAdmin && $EnforceMfaSetting === EnforceMfa::SysAdmins)
            || ($isAdmin && $EnforceMfaSetting === EnforceMfa::Admins)
            || $EnforceMfaSetting === EnforceMfa::Everyone;
    }
}

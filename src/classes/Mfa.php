<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @author    Marcel Bolten
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Services\Filter;
use Elabftw\Services\MpdfQrProvider;
use RobThree\Auth\TwoFactorAuth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use function time;

/**
 * Provide methods for multi/two-factor authentication
 */
class Mfa
{
    /** @var Session $Session Current session */
    public $Session;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var TwoFactorAuth $TwoFactorAuth PHP Class for handling two/multi-factor authentication */
    private $TwoFactorAuth;

    /** @var Request $Request Current request */
    private $Request;

    /**
     * Constructor
     *
     * @param Request $request
     * @param Session<mixed> $session
     */
    public function __construct(Request $request, Session $session)
    {
        $this->TwoFactorAuth = new TwoFactorAuth('eLabFTW', 6, 30, 'sha1', new MpdfQrProvider());
        $this->Db = Db::getConnection();
        $this->Request = $request;
        $this->Session = $session;
    }

    /**
     * Generate a new MFA secret
     * Redirect to multi-factor code submission
     *
     * @param string $redirect Where user will be redirected to after code submission
     * @return string location where MFA verification takes place
     */
    public function enable(string $redirect): string
    {
        // Need to request verification code to confirm user got secret and can authenticate in the future by MFA
        $this->Session->set('mfa_secret', $this->TwoFactorAuth->createSecret(160));
        $this->Session->set('enable_mfa', true);
        $this->Session->set('mfa_redirect', $redirect);

        return (string) '../../login.php';
    }

    /**
     * Get QR code image with MFA secret as data URI
     *
     * @param string $email
     * @return string
     */
    public function getQRCodeImageAsDataUri(string $email): string
    {
        return $this->TwoFactorAuth->getQRCodeImageAsDataUri($email, $this->Session->get('mfa_secret'));
    }

    /**
     * Abort enable MFA
     *
     * @return string previously specified redirect location
     */
    public function abortEnable(): string
    {
        $this->Session->getFlashBag()->add('ko', _('Two Factor Authentication not enabled!'));

        return (string) $this->cleanup(true);
    }

    /**
     * Save secret in database
     *
     * @return string previously specified redirect location
     */
    public function saveSecret(): string
    {
        $mfaSecret = $this->Session->get('mfa_secret');
        $userid = $this->Session->get('userid');
        $sql = 'UPDATE users SET mfa_secret = :mfa_secret WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':mfa_secret', $mfaSecret);
        $req->bindParam(':userid', $userid);
        $this->Db->execute($req);

        $this->Session->getFlashBag()->add('ok', _('Two Factor Authentication enabled.'));

        return $this->cleanup(true);
    }

    /**
     * Cleanup after two-factor authentication
     *
     * @param bool $enable
     * @return string previously specified redirect location
     */
    public function cleanup($enable = false): string
    {
        if ($enable) {
            $this->Session->remove('enable_mfa');
        }
        $this->Session->remove('mfa_secret');
        $location = $this->Session->get('mfa_redirect');
        $this->Session->remove('mfa_redirect');
        return (string) $location;
    }

    /**
     * Disable two-factor authentication for user
     *
     * @param int $userid
     * @return bool true if successful
     */
    public function disable(int $userid): bool
    {
        $sql = 'UPDATE users SET mfa_secret = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid);
        $this->Db->execute($req);

        if ($userid == $this->Session->get('userid')) {
            $this->Session->remove('mfa_verified');
        }

        return (bool) $req->rowCount();
    }

    /**
     * Test if we need to verify MFA code
     *
     * @param string $redirect Where user will be redirected to after code submission
     * @return bool true if we need to verify MFA code
     */
    public function needVerification(int $userid, string $redirect): bool
    {
        $MFASecret = $this->getSecret($userid);
        if ($MFASecret !== '' && !$this->Session->has('mfa_verified')) {
            $this->Session->set('mfa_secret', $MFASecret);
            $this->Session->set('mfa_redirect', $redirect);
            return true;
        }
        return false;
    }

    /**
     * Verify the MFA code
     *
     * @return bool
     */
    public function verifyCode(): bool
    {
        if ($this->TwoFactorAuth->verifyCode($this->Session->get('mfa_secret'), Filter::sanitize((string) $this->Request->request->get('mfa_code')), 2)) {
            $this->Session->set('mfa_verified', time());
            return true;
        }
        $this->Session->getFlashBag()->add('ko', _('Code not verified.'));
        return false;
    }

    /**
     * Load MFA secret of user from database if exists
     *
     * @param int $userid
     * @return string MFA secret or an empty string if there is no secret
     */
    private function getSecret(int $userid)
    {
        $sql = 'SELECT mfa_secret FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid);
        $this->Db->execute($req);
        $res = $req->fetchColumn();

        // No need to check for $res = false or null as casting takes care of it.
        return (string) $res;
    }
}

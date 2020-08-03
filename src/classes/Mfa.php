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
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     * @param Session $session
     */
    public function __construct(Request $request, Session $session)
    {
        $this->TwoFactorAuth = new TwoFactorAuth('eLabFTW', 6, 30, 'sha1', new MpdfQrProvider());
        $this->Db = Db::getConnection();
        $this->Request = $request;
        $this->Session = $session;
    }

    /**
     * Test if user has 2FA activated
     * Redirect to multi-factor code submission if active
     *
     * @param string $redirect Where user will be redirected to after code submission
     * @return void
     */
    public function needVerification(int $userid, string $redirect): void
    {
        $MFASecret = $this->getSecret($userid);
        if ($MFASecret && !$this->Session->has('mfa_verified')) {
            $this->Session->set('mfa_secret', $MFASecret);
            $this->Session->set('mfa_redirect', $redirect);

            $Response = new RedirectResponse('../../mfa.php');
            $Response->send();
            exit();
        }
    }

    /**
     * Load MFA secret of user from database if exists
     *
     * @param int $userid
     * @return mixed MFA secret or false
     */
    public function getSecret(int $userid)
    {
        $sql = 'SELECT mfa_secret FROM users WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid);
        $this->Db->execute($req);
        $res = $req->fetchColumn();

        if ($res === null || $res === false) {
            return (string) $res;
        }
        return false;
    }

    /**
     * Generate a new MFA secret
     * Redirect to multi-factor code submission
     *
     * @param string $redirect Where user will be redirected to after code submission
     * @return void
     */
    public function enable(string $redirect): void
    {
        // Need to request verification code to confirm user got secret and can authenticate in the future by MFA
        $this->Session->set('mfa_secret', $this->TwoFactorAuth->createSecret(160));
        $this->Session->set('enable_mfa', true);
        $this->Session->set('mfa_redirect', $redirect);

        $Response = new RedirectResponse('../../mfa.php');
        $Response->send();
        exit;
    }

    /**
     * Save secret in database
     * Redirect to previously specified location
     *
     * @return void
     */
    public function saveSecret(): void
    {
        $mfaSecret = $this->Session->get('mfa_secret');
        $userid = $this->Session->get('userid');
        $sql = 'UPDATE users SET mfa_secret = :mfa_secret WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':mfa_secret', $mfaSecret);
        $req->bindParam(':userid', $userid);
        $this->Db->execute($req);

        $this->Session->getFlashBag()->add('ok', _('Two Factor Authentication enabled.'));
        $location = $this->Session->get('mfa_redirect');

        $this->Session->remove('mfa_secret');
        $this->Session->remove('enable_mfa');
        $this->Session->remove('mfa_redirect');

        $Response = new RedirectResponse($location);
        $Response->send();
        exit;
    }

    /**
     * Disable two-factor authentication for user
     *
     * @param int $uderid
     * @return void
     */
    public function disable(int $userid): void
    {
        $sql = 'UPDATE users SET mfa_secret = null WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $userid);
        $this->Db->execute($req);
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
     * Verify the MFA code
     *
     * @return bool
     */
    public function verifyCode(): bool
    {
        if ($this->TwoFactorAuth->verifyCode($this->Session->get('mfa_secret'), Filter::sanitize((string) $this->Request->request->get('mfa_code')))) {
            $this->Session->set('mfa_verified', time());
            return true;
        }
        $this->Session->getFlashBag()->add('ko', _('Code not verified.'));
        return false;
    }
}

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

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use PDO;
use RobThree\Auth\TwoFactorAuth;
use RuntimeException;

/**
 * Provide methods for multi/two-factor authentication
 */
class MfaHelper
{
    /** @var string ISSUER will be displayed in the app as issuer name */
    private const ISSUER = 'eLabFTW';

    /** @var int DIGITS number of digits the resulting codes will be */
    private const DIGITS = 6;

    /** @var int PERIOD number of seconds a code will be valid */
    private const PERIOD = 30;

    /** @var string ALGO algorithm used */
    private const ALGO = 'sha1';

    /** @var int DISCREPENCY discrepency parameter to verify the code */
    private const DISCREPENCY = 2;

    /** @var int MFA_SECRET_BITS entropy for the mfa secret */
    private const SECRET_BITS = 160;

    /** @var int $userid */
    public $userid;

    /** @var string|null $secret */
    public $secret;

    /** @var Db $Db SQL Database */
    protected $Db;

    /** @var TwoFactorAuth $TwoFactorAuth PHP Class for handling two/multi-factor authentication */
    private $TwoFactorAuth;

    public function __construct(int $userid, ?string $secret = null)
    {
        $this->TwoFactorAuth = new TwoFactorAuth(
            self::ISSUER,
            self::DIGITS,
            self::PERIOD,
            self::ALGO,
            new MpdfQrProvider(),
        );
        $this->Db = Db::getConnection();
        $this->userid = $userid;
        $this->secret = $secret;
    }

    public function getQRCodeImageAsDataUri(string $email): string
    {
        return $this->TwoFactorAuth->getQRCodeImageAsDataUri($email, $this->secret);
    }

    public function generateSecret(): string
    {
        return $this->TwoFactorAuth->createSecret(self::SECRET_BITS);
    }

    public function saveSecret(): void
    {
        if ($this->secret === null) {
            throw new RuntimeException('No secret to save!');
        }
        $this->toggleSecret($this->secret);
    }

    public function removeSecret(): void
    {
        $this->toggleSecret();
    }

    public function verifyCode(string $code): bool
    {
        $code = Filter::sanitize($code);
        return $this->TwoFactorAuth->verifyCode($this->secret, $code, self::DISCREPENCY);
    }

    // only used to emulate the phone app (in MfaCode)
    public function getCode(): string
    {
        return $this->TwoFactorAuth->getCode($this->secret);
    }

    private function toggleSecret(?string $secret = null): void
    {
        $sql = 'UPDATE users SET mfa_secret = :secret WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':secret', $secret);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}

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
use Elabftw\Exceptions\ImproperActionException;
use PDO;
use RobThree\Auth\Algorithm;
use RobThree\Auth\TwoFactorAuth;
use RuntimeException;

/**
 * Provide methods for multi/two-factor authentication
 */
final class MfaHelper
{
    /** @var string ISSUER will be displayed in the app as issuer name */
    private const ISSUER = 'eLabFTW';

    /** @var int DIGITS number of digits the resulting codes will be */
    private const DIGITS = 6;

    /** @var int PERIOD number of seconds a code will be valid */
    private const PERIOD = 30;

    /** @var int DISCREPANCY discrepancy parameter to verify the code */
    private const DISCREPANCY = 2;

    /** @var int MFA_SECRET_BITS entropy for the mfa secret */
    private const SECRET_BITS = 160;

    protected Db $Db;

    private TwoFactorAuth $TwoFactorAuth;

    public function __construct(public int $userid, public ?string $secret = null)
    {
        $this->TwoFactorAuth = new TwoFactorAuth(
            new MpdfQrProvider(),
            self::ISSUER,
            self::DIGITS,
            self::PERIOD,
            Algorithm::Sha1,
        );
        $this->Db = Db::getConnection();
    }

    public function getQRCodeImageAsDataUri(string $email): string
    {
        if ($this->secret === null) {
            throw new ImproperActionException('Secret is null!');
        }
        return $this->TwoFactorAuth->getQRCodeImageAsDataUri($email, $this->secret);
    }

    public function generateSecret(): string
    {
        return $this->TwoFactorAuth->createSecret(self::SECRET_BITS);
    }

    public function saveSecret(): bool
    {
        return $this->toggleSecret($this->secret);
    }

    public function removeSecret(): bool
    {
        return $this->toggleSecret();
    }

    public function verifyCode(string $code): bool
    {
        if ($this->secret === null) {
            throw new RuntimeException('No secret to verify!');
        }
        return $this->TwoFactorAuth->verifyCode($this->secret, $code, self::DISCREPANCY);
    }

    /**
     * only used to emulate the phone app (in MfaCode)
     * @psalm-suppress PossiblyNullArgument
     */
    public function getCode(): string
    {
        return $this->TwoFactorAuth->getCode($this->secret);
    }

    private function toggleSecret(?string $secret = null): bool
    {
        $sql = 'UPDATE users SET mfa_secret = :secret WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':secret', $secret);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }
}

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

use Elabftw\Elabftw\Tools;
use RobThree\Auth\Algorithm;
use RobThree\Auth\TwoFactorAuth;

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

    public string $secret;

    private TwoFactorAuth $TwoFactorAuth;

    public function __construct(public ?string $maybeSecret = null)
    {
        $this->TwoFactorAuth = new TwoFactorAuth(
            new MpdfQrProvider(),
            self::ISSUER,
            self::DIGITS,
            self::PERIOD,
            Algorithm::Sha1,
        );
        $this->secret = $maybeSecret ?? $this->generateSecret();
    }

    public function getQRCodeImageAsDataUri(): string
    {
        // the first arg is a label, we do not use it
        return $this->TwoFactorAuth->getQRCodeImageAsDataUri(Tools::getUuidv4(), $this->secret);
    }

    public function verifyCode(string $code): bool
    {
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

    private function generateSecret(): string
    {
        return $this->TwoFactorAuth->createSecret(self::SECRET_BITS);
    }
}

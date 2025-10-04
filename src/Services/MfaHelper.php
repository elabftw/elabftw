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

use Elabftw\Elabftw\Env;
use RobThree\Auth\Algorithm;
use RobThree\Auth\TwoFactorAuth;

/**
 * Provide methods for multi/two-factor authentication
 */
final class MfaHelper
{
    // number of digits the resulting codes will be
    private const int DIGITS = 6;

    // number of seconds a code will be valid
    private const int PERIOD = 30;

    // discrepancy parameter to verify the code
    private const int DISCREPANCY = 2;

    // entropy for the mfa secret
    private const int SECRET_BITS = 160;

    public readonly string $secret;

    private readonly TwoFactorAuth $TwoFactorAuth;

    public function __construct(?string $maybeSecret = null)
    {
        $siteUrl = parse_url(Env::asUrl('SITE_URL'));

        $this->TwoFactorAuth = new TwoFactorAuth(
            new MpdfQrProvider(),
            sprintf('eLabFTW %s', $siteUrl['host'] ?? ''),
            self::DIGITS,
            self::PERIOD,
            Algorithm::Sha1,
        );
        $this->secret = $maybeSecret ?? $this->generateSecret();
    }

    public function getQRCodeImageAsDataUri(string $email): string
    {
        return $this->TwoFactorAuth->getQRCodeImageAsDataUri($email, $this->secret);
    }

    public function verifyCode(string $code): bool
    {
        return $this->TwoFactorAuth->verifyCode($this->secret, $code, self::DISCREPANCY);
    }

    /**
     * only used to emulate the phone app (in MfaCode)
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

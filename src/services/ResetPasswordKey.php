<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\ExistingUser;
use Elabftw\Models\Users;
use function explode;
use function implode;
use function sprintf;
use function time;

/**
 * Generator and validator for key in reset password feature
 */
class ResetPasswordKey
{
    // time in minutes after which the reset link is expired
    public const LINK_LIFETIME = 15;

    // this is our separator for separating the email and deadline encrypted in the key
    // it doesn't need to be secret or unique, just random enough so it's not found in the email
    protected const SEPARATOR = '17E8C262D020414D959A1ELABFTWIZDABESTELN';

    /**
     * $now is the time in epoch
     * $secretKey is the instance's key from config file
     */
    public function __construct(private int $now, private string $secretKey)
    {
    }

    public function generate(string $email): string
    {
        // the deadline is the epoch of now + link lifetime
        $deadline = $this->now + (self::LINK_LIFETIME * 60);

        // values are concatenated with the separator
        $cleartext = implode(self::SEPARATOR, array($email, $deadline));

        // and finally encrypted with the instance's secret key
        return Crypto::encrypt($cleartext, Key::loadFromAsciiSafeString($this->secretKey));
    }

    public function validate(string $key): Users
    {
        $decryptedKey = Crypto::decrypt($key, Key::loadFromAsciiSafeString($this->secretKey));
        [$email, $deadline] = explode(self::SEPARATOR, $decryptedKey);

        if ($deadline < $this->now) {
            throw new ImproperActionException(sprintf(_('This link has expired! Password reset links are only valid for %s minutes.'), self::LINK_LIFETIME));
        }

        // if the key is correct, we now have an ExistingUser here
        return ExistingUser::fromEmail($email);
    }
}

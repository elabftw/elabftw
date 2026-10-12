<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Env;
use Elabftw\Exceptions\AppException;

use function sprintf;

/**
 * Encrypts a webhook secret at rest with SECRET_KEY, like Config does for smtp_password
 * and ldap_password.
 *
 * It cannot be hashed the way an api key is, because it is needed in clear to sign the
 * deliveries. It lives here rather than on the model because the two places that need it
 * are far apart: the model when a webhook is created or read back, and the dispatcher when
 * it signs a delivery.
 */
final class WebhookSecret
{
    public static function encrypt(string $secret): string
    {
        return Crypto::encrypt($secret, Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
    }

    public static function decrypt(string $encrypted): string
    {
        try {
            return Crypto::decrypt($encrypted, Key::loadFromAsciiSafeString(Env::asString('SECRET_KEY')));
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            throw new AppException(sprintf('Error decrypting webhook secret: %s. This can be caused by having a different SECRET_KEY than the one that was used to encrypt it. Delete the webhook and create it again.', $e->getMessage()), 500);
        }
    }
}

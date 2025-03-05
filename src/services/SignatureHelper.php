<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\MinisignKeys;
use Elabftw\Enums\Meaning;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use ParagonIE\ConstantTime\Base64;

use function sodium_crypto_generichash;

use const SODIUM_CRYPTO_GENERICHASH_BYTES_MAX;
use const JSON_THROW_ON_ERROR;

/**
 * Helper class for minisign compatible signatures
 * minisign by Frank Denis: https://jedisct1.github.io/minisign/#secret-key-format
 * Inspired by the code from https://github.com/soatok/minisign-php by Soatok Dreamseeker
 */
final class SignatureHelper
{
    public const UNTRUSTED_COMMENT_PREFIX = 'untrusted comment: ';

    public const KEY_REGEX = '#^' . self::UNTRUSTED_COMMENT_PREFIX . '(.+?)[\r\n\s]+([A-Za-z0-9+/=]+)[\s]+?$#';

    private const TRUSTED_COMMENT_PREFIX = 'trusted comment: ';

    // hashed DSA: we use Ed25519ph from https://datatracker.ietf.org/doc/html/rfc8032#section-5.1
    private const HASHED_DSA = 'ED';

    public function __construct(private Users $Users) {}

    public function serializeSignature(string $privkey, string $passphrase, string $message, Meaning $meaning): string
    {
        $Key = MinisignKeys::deserialize($privkey, $passphrase);
        // because we use Ed25519ph (pre-hashed), we hash the message before signing it
        $signature = sodium_crypto_sign_detached(
            sodium_crypto_generichash($message, '', SODIUM_CRYPTO_GENERICHASH_BYTES_MAX),
            $Key->priv
        );

        // trusted comment: this comment is signed and contains metadata about the signature
        // we encode it in JSON to make it easy to parse by an eventual downstream app
        $trustedCommentArr = array(
            'firstname' => $this->Users->userData['firstname'],
            'lastname' => $this->Users->userData['lastname'],
            'email' => $this->Users->userData['email'],
            'created_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'site_url' => Config::fromEnv('SITE_URL'),
            'created_by' => sprintf('eLabFTW %d', App::INSTALLED_VERSION_INT),
            'meaning' => $meaning->name,
        );
        $trustedCommentJson = json_encode($trustedCommentArr, JSON_THROW_ON_ERROR);
        // this is the global signature for the signature and comment combined
        $globalSignature = sodium_crypto_sign_detached($signature . $trustedCommentJson, $Key->priv);

        $firstLine = sprintf(
            "%selabftw/%d: signature from key %s\n",
            self::UNTRUSTED_COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            $Key->getIdHex(),
        );

        return $firstLine .
            Base64::encode(self::HASHED_DSA . $Key->id . $signature) . "\n" .
            self::TRUSTED_COMMENT_PREFIX . $trustedCommentJson . "\n" .
            Base64::encode($globalSignature) . "\n";
    }
}

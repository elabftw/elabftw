<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\SignatureHelper;
use function hash_equals;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\ConstantTime\Binary;
use function preg_match;
use function sodium_crypto_generichash;
use const SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
use const SODIUM_CRYPTO_SIGN_SECRETKEYBYTES;
use function unpack;

class SignatureKeys
{
    public const KEYID_BYTES = 8;

    private const REGEX = '#^' . SignatureHelper::COMMENT_PREFIX . '(.+?)[\r\n\s]+([A-Za-z0-9+/=]+)[\s]+?$#';

    private const KDF_LENGTH = 104;

    // ed25519
    private const SIGNATURE_ALGO = 'Ed';

    public function __construct(
        public readonly string $signatureAlgo,
        public readonly string $id,
        /** @var non-empty-string */
        public readonly string $priv,
        public readonly string $pub,
        public readonly string $salt,
        public readonly string $derivedKey,
    ) {
    }

    public static function generate(string $passphrase): self
    {
        // Generate a salt for key derivation
        // SCRYPT_SALSA208SHA256 salt should be crypto_pwhash_scryptsalsa208sha256_SALTBYTES bytes
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES);

        // derive a key from the passphrase
        $derivedKey = self::kdf(
            $passphrase,
            $salt,
            SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE,
        );

        // the key ID is 8 random bytes to give a hint about which secret key was used to sign a message
        $id = random_bytes(self::KEYID_BYTES);

        // generate a random Ed25519 keypair as one string
        $keypair = sodium_crypto_sign_keypair();
        $pub = sodium_crypto_sign_publickey($keypair);
        /** @var non-empty-string */
        $priv = sodium_crypto_sign_secretkey($keypair);

        return new self(self::SIGNATURE_ALGO, $id, $priv, $pub, $salt, $derivedKey);
    }

    /**
     * Secret key format from: https://jedisct1.github.io/minisign/#secret-key-format
     * untrusted comment: <arbitrary text>
     * base64(<signature_algorithm> || <kdf_algorithm> || <cksum_algorithm> || <kdf_salt> || <kdf_opslimit> || <kdf_memlimit> || <keynum_sk>)
     */
    public static function deserialize(string $secretKey, string $passphrase): self
    {
        $sk = array();
        if (!preg_match(self::REGEX, $secretKey, $sk)) {
            throw new ImproperActionException(_('Invalid secret key format!'));
        }
        $decoded = Base64::decode($sk[2]);
        $sigAlg = Binary::safeSubstr($decoded, 0, 2);
        // we don't care about kdfAlgo or cksumAlgo
        $salt = Binary::safeSubstr($decoded, 6, 32);
        $packedOpsLimit = Binary::safeSubstr($decoded, 38, 4);
        $packedMemLimit = Binary::safeSubstr($decoded, 46, 4);
        $unpackedOpsLimit = unpack('V', $packedOpsLimit);
        $unpackedMemLimit = unpack('V', $packedMemLimit);
        if ($unpackedOpsLimit === false || $unpackedMemLimit === false) {
            throw new ImproperActionException('Error unpacking limits for kdf');
        }
        $kdfOpsLimit = (int) $unpackedOpsLimit[1];
        $kdfMemLimit = (int) $unpackedMemLimit[1];
        $derivedKey = self::kdf($passphrase, $salt, $kdfOpsLimit, $kdfMemLimit);
        /** @psalm-suppress RedundantCast */
        $remainder = (string) (Binary::safeSubstr($decoded, 54, 136) ^ $derivedKey);
        // Note: had to change second arg from 2 to 0 here to make checksum work! (from php impl)
        $keyId = Binary::safeSubstr($remainder, 0, SignatureHelper::KEYID_BYTES);
        /** @var non-empty-string */
        $priv = Binary::safeSubstr($remainder, SignatureHelper::KEYID_BYTES, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
        $pub = Binary::safeSubstr($remainder, SignatureHelper::KEYID_BYTES + SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $checksum = Binary::safeSubstr($remainder, 72, 32);

        // verify checksum
        $expectedHash = sodium_crypto_generichash($sigAlg . $keyId . $priv);
        if (!hash_equals($expectedHash, $checksum)) {
            throw new ImproperActionException(_('Error decrypting private key. Are you certain of the passphrase?'));
        }

        return new self($sigAlg, $keyId, $priv, $pub, $salt, $derivedKey);
    }

    private static function kdf(string $passphrase, string $salt, int $kdfOpsLimit, int $kdfMemLimit): string
    {
        // derive a key from the passphrase
        $derivedKey = sodium_crypto_pwhash_scryptsalsa208sha256(
            self::KDF_LENGTH,
            $passphrase,
            $salt,
            $kdfOpsLimit,
            $kdfMemLimit,
        );
        // clear passphrase from memory: we don't need it anymore
        sodium_memzero($passphrase);
        return $derivedKey;
    }
}

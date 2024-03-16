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
use Elabftw\Services\Sigkeys;
use function hash_equals;
use ParagonIE\ConstantTime\Base64;

use ParagonIE\ConstantTime\Binary;
use function preg_match;
use function sodium_crypto_generichash;
use const SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
use const SODIUM_CRYPTO_SIGN_SECRETKEYBYTES;
use function unpack;

class SigSecretKey
{
    private const REGEX = '#^' . Sigkeys::COMMENT_PREFIX . '(.+?)[\r\n\s]+([A-Za-z0-9+/=]+)[\s]+?$#';

    private const KDF_LENGTH = 104;

    public function __construct(
        public readonly string $signatureAlgo,
        public readonly string $id,
        public readonly string $key,
        public readonly string $pk,
    ) {
    }

    public static function kdf(string $passphrase, string $salt, int $kdfOpsLimit, int $kdfMemLimit): string
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
        $kdfSalt = Binary::safeSubstr($decoded, 6, 32);
        $packedOpsLimit = Binary::safeSubstr($decoded, 38, 4);
        $packedMemLimit = Binary::safeSubstr($decoded, 46, 4);
        $kdfOpsLimit = (int) unpack('V', $packedOpsLimit)[1];
        $kdfMemLimit = (int) unpack('V', $packedMemLimit)[1];
        $kdfOutput = self::kdf($passphrase, $kdfSalt, $kdfOpsLimit, $kdfMemLimit);
        $remainder = (string) (Binary::safeSubstr($decoded, 54, 136) ^ $kdfOutput);
        // Note: had to change second arg from 2 to 0 here to make checksum work! (from php impl)
        $keyId = Binary::safeSubstr($remainder, 0, Sigkeys::KEYID_BYTES);
        $ed25519sk = Binary::safeSubstr($remainder, Sigkeys::KEYID_BYTES, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
        $ed25519pk = Binary::safeSubstr($remainder, Sigkeys::KEYID_BYTES + SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $checksum = Binary::safeSubstr($remainder, 72, 32);

        // verify checksum
        $expectedHash = sodium_crypto_generichash($sigAlg . $keyId . $ed25519sk);
        if (!hash_equals($expectedHash, $checksum)) {
            throw new ImproperActionException(_('Error decrypting private key. Are you certain of the passphrase?'));
        }

        return new self($sigAlg, $keyId, $ed25519sk, $ed25519pk);
    }
}

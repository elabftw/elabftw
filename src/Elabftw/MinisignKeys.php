<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Services\SignatureHelper;
use ParagonIE\ConstantTime\Base64;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\ConstantTime\Hex;
use SensitiveParameter;

use function hash_equals;
use function preg_match;
use function sodium_crypto_generichash;
use function sodium_memzero;
use function unpack;

use const SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES;
use const SODIUM_CRYPTO_SIGN_SECRETKEYBYTES;
use const SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES;
use const SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE;
use const SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE;

/**
 * This class is a representation of a minisign key
 * minisign by Frank Denis: https://jedisct1.github.io/minisign/#secret-key-format
 * Inspired by the code from https://github.com/soatok/minisign-php by Soatok Dreamseeker
 */
final readonly class MinisignKeys
{
    // ed25519
    private const string SIGNATURE_ALGO = 'Ed';

    private const int KEYID_BYTES = 8;

    private const int KDF_LENGTH = 104;

    // our key derivation algo: scrypt
    private const string KDF_ALGO = 'Sc';

    // our checksum algo: blake2
    private const string CKSUM_ALGO = 'B2';

    public function __construct(
        public string $signatureAlgo,
        public string $id,
        /** @var non-empty-string */
        public string $priv,
        public string $pub,
        public string $salt,
        public string $derivedKey,
    ) {}

    public function getIdHex(): string
    {
        return Hex::encode($this->id);
    }

    public static function generate(#[SensitiveParameter] string $passphrase): self
    {
        if (empty($passphrase)) {
            throw new ImproperActionException(_('Empty passphrase provided!'));
        }
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
    public static function deserialize(string $secretKey, #[SensitiveParameter] string $passphrase): self
    {
        $sk = array();
        if (!preg_match(SignatureHelper::KEY_REGEX, $secretKey, $sk)) {
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
            // Note: this error message is not translated because unless you're a cryptogeek, this means nothing in any language
            throw new ImproperActionException('Something went wrong while decrypting your private key: error unpacking ops or mem limits for key derivation function');
        }
        $kdfOpsLimit = (int) $unpackedOpsLimit[1];
        $kdfMemLimit = (int) $unpackedMemLimit[1];
        $derivedKey = self::kdf($passphrase, $salt, $kdfOpsLimit, $kdfMemLimit);
        $remainder = Binary::safeSubstr($decoded, 54, 136) ^ $derivedKey;
        // Note: had to change second arg from 2 to 0 here to make checksum work! (from php impl)
        $id = Binary::safeSubstr($remainder, 0, self::KEYID_BYTES);
        /** @var non-empty-string */
        $priv = Binary::safeSubstr($remainder, self::KEYID_BYTES, SODIUM_CRYPTO_SIGN_SECRETKEYBYTES);
        $pub = Binary::safeSubstr($remainder, self::KEYID_BYTES + SODIUM_CRYPTO_SIGN_SECRETKEYBYTES, SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES);
        $checksum = Binary::safeSubstr($remainder, 72, 32);

        // verify checksum
        $expected = sodium_crypto_generichash($sigAlg . $id . $priv);
        if (!hash_equals($expected, $checksum)) {
            throw new ImproperActionException(_('Could not decrypt private key. Are you certain of the passphrase?'));
        }

        return new self($sigAlg, $id, $priv, $pub, $salt, $derivedKey);
    }

    /**
     * Secret key format https://jedisct1.github.io/minisign/#secret-key-format
     * untrusted comment: <arbitrary text>
     * base64(<signature_algorithm> || <kdf_algorithm> || <cksum_algorithm> ||
     * <kdf_salt> || <kdf_opslimit> || <kdf_memlimit> || <keynum_sk>)
     */
    public function serializeSk(): string
    {
        $firstLine = sprintf(
            "%selabftw/%d: encrypted secret key %s\n",
            SignatureHelper::UNTRUSTED_COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            $this->getIdHex(),
        );
        $toEncode = self::SIGNATURE_ALGO . self::KDF_ALGO . self::CKSUM_ALGO . $this->salt;
        $toEncode .= pack('V', SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE) . "\0\0\0\0";
        $toEncode .= pack('V', SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE) . "\0\0\0\0";
        $checksum = sodium_crypto_generichash(self::SIGNATURE_ALGO . $this->id . $this->priv);
        $toXor = $this->id . $this->priv . $checksum;
        $toEncode .= $this->derivedKey ^ $toXor;
        return $firstLine . Base64::encode($toEncode) . "\n";
    }

    /**
     * Public key format https://jedisct1.github.io/minisign/#public-key-format
     * untrusted comment: <arbitrary text>
     * base64(<signature_algorithm> || <key_id> || <public_key>)
     */
    public function serializePk(): string
    {
        return sprintf(
            "%selabftw/%d: public key %s\n%s\n",
            SignatureHelper::UNTRUSTED_COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            $this->getIdHex(),
            Base64::encodeUnpadded(self::SIGNATURE_ALGO . $this->id . $this->pub),
        );
    }

    /**
     * This function is responsible for generating a key derived from a passphrase.
     * It allows encrypting the private key with a passphrase.
     */
    private static function kdf(#[SensitiveParameter] string $passphrase, string $salt, int $kdfOpsLimit, int $kdfMemLimit): string
    {
        // derive a key from the passphrase
        $derivedKey = sodium_crypto_pwhash_scryptsalsa208sha256(
            self::KDF_LENGTH,
            $passphrase,
            $salt,
            $kdfOpsLimit,
            $kdfMemLimit,
        );
        // zero out passphrase in memory: we don't need it anymore
        sodium_memzero($passphrase);
        return $derivedKey;
    }
}

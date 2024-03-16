<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\App;
use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\SigSecretKey;
use Elabftw\Enums\Meaning;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use function pack;

use ParagonIE\ConstantTime\Base64;
use ParagonIE\ConstantTime\Hex;
use function random_bytes;
use function sodium_crypto_generichash;

class Sigkeys
{
    public const COMMENT_PREFIX = 'untrusted comment: ';

    public const KEYID_BYTES = 8;

    private const TRUSTED_COMMENT_PREFIX = 'trusted comment: ';

    // ed25519
    private const SIGNATURE_ALGO = 'Ed';

    // hashed DSA: we use Ed25519ph from https://datatracker.ietf.org/doc/html/rfc8032#section-5.1
    private const HASHED_DSA = 'ED';

    // scrypt
    private const KDF_ALGO = 'Sc';

    // blake2
    private const CKSUM_ALGO = 'B2';

    protected Db $Db;

    public function __construct(private Users $Users)
    {
        $this->Db = Db::getConnection();
    }

    public function create(string $passphrase): bool
    {
        $keypair = $this->generateKeypair($passphrase);
        $sql = 'UPDATE users SET sig_pubkey = :sig_pubkey, sig_privkey = :sig_privkey WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':sig_pubkey', $keypair['pubkey']);
        $req->bindParam(':sig_privkey', $keypair['privkey']);
        $req->bindParam(':userid', $this->Users->userid);
        return $req->execute();
    }

    public function sign(string $secretKey, string $passphrase, string $message, Meaning $meaning): string
    {
        $SigSecretKey = SigSecretKey::deserialize($secretKey, $passphrase);
        // because we use Ed25519ph (pre-hashed), we hash the message before signing it
        $signature = sodium_crypto_sign_detached(
            sodium_crypto_generichash($message, '', SODIUM_CRYPTO_GENERICHASH_BYTES_MAX),
            $SigSecretKey->key
        );

        // trusted comment: this comment is signed and contains metadata about the signature
        $DateTime = new DateTimeImmutable();
        $trustedCommentArr = array(
            'firstname' => $this->Users->userData['firstname'],
            'lastname' => $this->Users->userData['lastname'],
            'email' => $this->Users->userData['email'],
            'created_at' => $DateTime->format(DateTimeImmutable::ATOM),
            'site_url' => Config::fromEnv('SITE_URL'),
            'created_by' => sprintf('eLabFTW %d', App::INSTALLED_VERSION_INT),
            'meaning' => $meaning->name,
        );
        $trustedComment = json_encode($trustedCommentArr, JSON_THROW_ON_ERROR);
        // this is the global signature for the signature and comment combined
        $globalSignature = sodium_crypto_sign_detached($signature . $trustedComment, $SigSecretKey->key);

        $firstLine = sprintf(
            "%selabftw/%d: signature from key %s\n",
            self::COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            Hex::encode($SigSecretKey->id),
        );

        return $firstLine .
            Base64::encode(self::HASHED_DSA . $SigSecretKey->id . $signature) .
            "\n".
            self::TRUSTED_COMMENT_PREFIX .
            $trustedComment . "\n" .
            Base64::encode($globalSignature) . "\n";
    }

    private function serializePk(string $keyId, string $publicKey)
    {
        return sprintf(
            "%selabftw/%d: public key %s\n%s\n",
            self::COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            Hex::encode($keyId),
            Base64::encodeUnpadded(self::SIGNATURE_ALGO . $keyId . $publicKey),
        );
    }

    private function serializeSk(SigSecretKey $secretKey, string $salt, string $derivedKey): string
    {
        $firstLine = sprintf(
            "%selabftw/%d: encrypted secret key %s\n",
            self::COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            Hex::encode($secretKey->id),
        );
        $toEncode = self::SIGNATURE_ALGO . self::KDF_ALGO . self::CKSUM_ALGO . $salt;
        $toEncode .= pack('V', SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE) . "\0\0\0\0";
        $toEncode .= pack('V', SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE) . "\0\0\0\0";
        $checksum = sodium_crypto_generichash(
            self::SIGNATURE_ALGO . $secretKey->id . $secretKey->key
        );
        $toXor = $secretKey->id . $secretKey->key . $checksum;
        $toEncode .= $derivedKey ^ $toXor;
        return $firstLine . Base64::encode($toEncode) . "\n";
    }

    private function generateKeypair(string $passphrase): array
    {
        // Generate a salt for key derivation
        // SCRYPT_SALSA208SHA256 salt should be crypto_pwhash_scryptsalsa208sha256_SALTBYTES bytes
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_SALTBYTES);

        // derive a key from the passphrase
        $derivedKey = SigSecretKey::kdf(
            $passphrase,
            $salt,
            SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE,
        );

        // the key ID is 8 random bytes to give a hint about which secret key was used to sign a message
        $keyId = random_bytes(self::KEYID_BYTES);

        // generate a random Ed25519 keypair as one string
        $keypair = sodium_crypto_sign_keypair();
        $pubkey = sodium_crypto_sign_publickey($keypair);
        $privkey = sodium_crypto_sign_secretkey($keypair);

        $SecretKey = new SigSecretKey(self::SIGNATURE_ALGO, $keyId, $privkey, $pubkey);

        return array(
            'pubkey' => $this->serializePk($keyId, $pubkey),
            'privkey' => $this->serializeSk($SecretKey, $salt, $derivedKey),
        );
    }
}

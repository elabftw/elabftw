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
use Elabftw\Elabftw\SignatureKeys;
use Elabftw\Enums\Meaning;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use function pack;

use ParagonIE\ConstantTime\Base64;
use ParagonIE\ConstantTime\Hex;
use function sodium_crypto_generichash;

class SignatureHelper
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
        $Key = SignatureKeys::generate($passphrase);

        $sql = 'UPDATE users SET sig_pubkey = :sig_pubkey, sig_privkey = :sig_privkey WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':sig_pubkey', $this->serializePk($Key));
        $req->bindValue(':sig_privkey', $this->serializeSk($Key));
        $req->bindParam(':userid', $this->Users->userid);
        return $req->execute();
    }

    public function serializeSignature(string $secretKey, string $passphrase, string $message, Meaning $meaning): string
    {
        $Key = SignatureKeys::deserialize($secretKey, $passphrase);
        // because we use Ed25519ph (pre-hashed), we hash the message before signing it
        $signature = sodium_crypto_sign_detached(
            sodium_crypto_generichash($message, '', SODIUM_CRYPTO_GENERICHASH_BYTES_MAX),
            $Key->priv
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
        $globalSignature = sodium_crypto_sign_detached($signature . $trustedComment, $Key->priv);

        $firstLine = sprintf(
            "%selabftw/%d: signature from key %s\n",
            self::COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            Hex::encode($Key->id),
        );

        return $firstLine .
            Base64::encode(self::HASHED_DSA . $Key->id . $signature) .
            "\n".
            self::TRUSTED_COMMENT_PREFIX .
            $trustedComment . "\n" .
            Base64::encode($globalSignature) . "\n";
    }

    /**
     * Public key format https://jedisct1.github.io/minisign/#public-key-format
     * untrusted comment: <arbitrary text>
     * base64(<signature_algorithm> || <key_id> || <public_key>)
     */
    private function serializePk(SignatureKeys $key): string
    {
        return sprintf(
            "%selabftw/%d: public key %s\n%s\n",
            self::COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            Hex::encode($key->id),
            Base64::encodeUnpadded(self::SIGNATURE_ALGO . $key->id . $key->pub),
        );
    }

    /**
     * Secret key format https://jedisct1.github.io/minisign/#secret-key-format
     * untrusted comment: <arbitrary text>
     * base64(<signature_algorithm> || <kdf_algorithm> || <cksum_algorithm> ||
     * <kdf_salt> || <kdf_opslimit> || <kdf_memlimit> || <keynum_sk>)
     */
    private function serializeSk(SignatureKeys $key): string
    {
        $firstLine = sprintf(
            "%selabftw/%d: encrypted secret key %s\n",
            self::COMMENT_PREFIX,
            App::INSTALLED_VERSION_INT,
            Hex::encode($key->id),
        );
        $toEncode = self::SIGNATURE_ALGO . self::KDF_ALGO . self::CKSUM_ALGO . $key->salt;
        $toEncode .= pack('V', SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_OPSLIMIT_INTERACTIVE) . "\0\0\0\0";
        $toEncode .= pack('V', SODIUM_CRYPTO_PWHASH_SCRYPTSALSA208SHA256_MEMLIMIT_INTERACTIVE) . "\0\0\0\0";
        $checksum = sodium_crypto_generichash(
            self::SIGNATURE_ALGO . $key->id . $key->priv
        );
        $toXor = $key->id . $key->priv . $checksum;
        $toEncode .= $key->derivedKey ^ $toXor;
        return $firstLine . Base64::encode($toEncode) . "\n";
    }
}

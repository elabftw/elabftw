<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\Db;
use Elabftw\Elabftw\Env;
use Elabftw\Services\Filter;
use PDO;

use function bin2hex;
use function hash_hmac;
use function random_bytes;
use function strlen;

/**
 * The cookie token acts as a key to login back; only its hash is stored in the database
 */
final class CookieToken
{
    private const int COOKIE_RANDOM_BYTES = 16;

    private const int COOKIE_HEX_LENGTH = self::COOKIE_RANDOM_BYTES * 2;

    private readonly string $token;

    private Db $Db;

    public function __construct(string $token)
    {
        $this->Db = Db::getConnection();
        $this->token = self::check($token);
    }

    /**
     * Save the token hash in the database for a user
     */
    public function saveToken(int $userid): bool
    {
        if ($this->token === '') {
            return true;
        }
        $sql = 'UPDATE users SET token_hash = :token_hash, token_created_at = NOW() WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':token_hash', $this->getTokenHash(), PDO::PARAM_LOB);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getTokenHash(): string
    {
        return hash_hmac('sha256', $this->token, Env::asString('SECRET_KEY'), true);
    }

    public static function generate(): string
    {
        return bin2hex(random_bytes(self::COOKIE_RANDOM_BYTES));
    }

    public static function fromScratch(): self
    {
        return new self(self::generate());
    }

    private static function check(string $token): string
    {
        // filter out any non hexit
        $token = Filter::hexits($token);
        // and check length
        if (strlen($token) !== self::COOKIE_HEX_LENGTH) {
            return '';
        }
        return $token;
    }
}

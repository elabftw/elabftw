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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Filter;
use PDO;

use function bin2hex;
use function hash;
use function random_bytes;

/**
 * The cookie "token" acts as a key to login back thanks to the cookie value and the value stored in database
 */
final class CookieToken
{
    /** cookie is a sha256 sum: 64 chars */
    private const int COOKIE_LENGTH = 64;

    private readonly string $token;

    private Db $Db;

    public function __construct(string $token)
    {
        $this->Db = Db::getConnection();
        $this->token = self::check($token);
    }

    /**
     * Save the token in the database for a user
     */
    public function saveToken(int $userid): bool
    {
        $sql = 'UPDATE users SET token = :token, token_created_at = NOW() WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':token', $this->getToken());
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public static function generate(): string
    {
        return hash('sha256', bin2hex(random_bytes(self::COOKIE_LENGTH / 4)));
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
        if (mb_strlen($token) !== self::COOKIE_LENGTH) {
            throw new IllegalActionException('Cookie token has invalid length!');
        }
        return $token;
    }
}

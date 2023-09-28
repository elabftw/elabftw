<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use function bin2hex;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Filter;
use function hash;
use PDO;
use function random_bytes;

/**
 * The cookie "token" acts as a key to login back thanks to the cookie value and the value stored in database
 */
class CookieToken
{
    /** cookie is a sha256 sum: 64 chars */
    private const COOKIE_LENGTH = 64;

    public string $token;

    private Db $Db;

    public function __construct(string $token = '')
    {
        $this->Db = Db::getConnection();
        $this->token = $this->setToken($token);
    }

    /**
     * Save the token in the database for a user
     */
    public function saveToken(int $userid): bool
    {
        $sql = 'UPDATE users SET token = :token, token_created_at = NOW() WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $this->token, PDO::PARAM_STR);
        $req->bindParam(':userid', $userid, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    private function setToken(string $token): string
    {
        if (empty($token)) {
            return $this->generateToken();
        }
        return self::check($token);
    }

    private function generateToken(): string
    {
        return hash('sha256', bin2hex(random_bytes(self::COOKIE_LENGTH / 4)));
    }

    private static function check(string $token): string
    {
        if (mb_strlen($token) !== self::COOKIE_LENGTH) {
            throw new IllegalActionException('Invalid cookie!');
        }
        // filter out any non hexit
        return Filter::hexits($token);
    }
}

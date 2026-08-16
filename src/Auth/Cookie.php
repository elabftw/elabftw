<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuthMethod;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthenticatorInterface;
use Override;
use PDO;

use function sprintf;

/**
 * Authenticate with the cookie
 */
final class Cookie implements AuthenticatorInterface
{
    public function __construct(private int $validityMinutes, private CookieToken $Token) {}

    #[Override]
    public function authenticate(): Authentication
    {
        $Db = Db::getConnection();
        // compare the hash of the provided token with the hash saved in SQL database
        $sql = sprintf(
            'SELECT userid
            FROM users WHERE token_hash = :token_hash AND token_created_at > NOW() - INTERVAL %d MINUTE LIMIT 1',
            $this->validityMinutes
        );
        $req = $Db->prepare($sql);
        $req->bindValue(':token_hash', $this->Token->getTokenHash(), PDO::PARAM_LOB);
        $Db->execute($req);
        $userid = (int) $req->fetchColumn();
        if ($userid === 0) {
            throw new UnauthorizedException();
        }
        return new Authentication($userid, AuthMethod::Cookie);
    }
}

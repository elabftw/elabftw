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

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Elabftw\Services\TeamsHelper;
use Override;

/**
 * Authenticate with the cookie
 */
final class Cookie implements AuthInterface
{
    public function __construct(private int $validityMinutes, private CookieToken $Token, private int $team) {}

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        $Db = Db::getConnection();
        // compare the provided token with the token saved in SQL database
        $sql = sprintf(
            'SELECT userid
            FROM users WHERE token = :token AND token_created_at + INTERVAL %d MINUTE > NOW() LIMIT 1',
            $this->validityMinutes
        );
        $req = $Db->prepare($sql);
        $req->bindValue(':token', $this->Token->getToken());
        $Db->execute($req);
        $userid = (int) $req->fetchColumn();
        if ($userid === 0) {
            throw new UnauthorizedException();
        }

        // when doing auth with cookie, we take the token_team value
        // make sure user is in team because we can't trust it
        $TeamsHelper = new TeamsHelper($this->team);
        if (!$TeamsHelper->isUserInTeam($userid)) {
            throw new UnauthorizedException();
        }

        return new AuthResponse()
            ->setAuthenticatedUserid($userid)
            ->setSelectedTeam($this->team);
    }
}

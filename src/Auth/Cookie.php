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

use Elabftw\Controllers\LoginController;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Services\TeamsHelper;
use Override;

/**
 * Authenticate with the cookie
 */
final class Cookie implements AuthInterface
{
    private Db $Db;

    private AuthResponse $AuthResponse;

    public function __construct(private int $validityMinutes, private int $enforceMfa, private CookieToken $Token, private int $team)
    {
        $this->Db = Db::getConnection();
        $this->AuthResponse = new AuthResponse();
    }

    #[Override]
    public function tryAuth(): AuthResponse
    {
        // compare the provided token with the token saved in SQL database
        $sql = sprintf(
            'SELECT userid, mfa_secret, auth_service, validated
            FROM users WHERE token = :token AND token_created_at + INTERVAL %d MINUTE > NOW() LIMIT 1',
            $this->validityMinutes
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':token', $this->Token->getToken());
        $this->Db->execute($req);
        if ($req->rowCount() !== 1) {
            throw new UnauthorizedException();
        }
        $res = $req->fetch();
        $userid = $res['userid'];

        // when doing auth with cookie, we take the token_team value
        // make sure user is in team because we can't trust it
        $TeamsHelper = new TeamsHelper($this->team);
        if (!$TeamsHelper->isUserInTeam($userid)) {
            throw new UnauthorizedException();
        }

        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->mfaSecret = $res['mfa_secret'];
        $this->AuthResponse->isValidated = (bool) $res['validated'];
        $this->AuthResponse->selectedTeam = $this->team;

        // Force user to login again to activate MFA if it is enforced for local auth and there is no mfaSecret
        if ($res['auth_service'] === LoginController::AUTH_LOCAL
            && Local::enforceMfa($this->AuthResponse, $this->enforceMfa)
        ) {
            throw new UnauthorizedException();
        }

        return $this->AuthResponse;
    }
}

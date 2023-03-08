<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Controllers\LoginController;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;

/**
 * Authenticate with the cookie
 */
class CookieAuth implements AuthInterface
{
    private Db $Db;

    private string $token;

    private int $tokenTeam;

    private AuthResponse $AuthResponse;

    public function __construct(string $token, string $tokenTeam, private array $configArr)
    {
        $this->Db = Db::getConnection();
        $this->token = Check::token($token);
        $this->tokenTeam = (int) Filter::sanitize($tokenTeam);
        $this->AuthResponse = new AuthResponse();
    }

    public function tryAuth(): AuthResponse
    {
        // compare the provided token with the token saved in SQL database
        $sql = 'SELECT `users`.`userid`, `users`.`mfa_secret`, `users`.`auth_service`,
                `groups`.`is_admin`, `groups`.`is_sysadmin`
            FROM `users`
            LEFT JOIN `groups` ON (`users`.`usergroup` = `groups`.`id`)
            WHERE `token` = :token
            LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $this->token);
        $this->Db->execute($req);
        if ($req->rowCount() !== 1) {
            throw new UnauthorizedException();
        }
        $res = $req->fetch();
        $userid = (int) $res['userid'];

        // when doing auth with cookie, we take the token_team value
        // make sure user is in team because we can't trust it
        $TeamsHelper = new TeamsHelper($this->tokenTeam);
        if (!$TeamsHelper->isUserInTeam($userid)) {
            throw new UnauthorizedException();
        }

        $this->AuthResponse->userid = $userid;
        $this->AuthResponse->mfaSecret = $res['mfa_secret'];
        $this->AuthResponse->selectedTeam = $this->tokenTeam;
        $this->AuthResponse->isAdmin = (bool) $res['is_admin'];
        $this->AuthResponse->isSysAdmin = (bool) $res['is_sysadmin'];

        // Force user to login again to activate MFA if it is enforced for local auth and there is no mfaSecret
        if ($res['auth_service'] === LoginController::AUTH_LOCAL
            && LocalAuth::enforceMfa($this->AuthResponse, (int) $this->configArr['enforce_mfa'])
        ) {
            throw new UnauthorizedException();
        }

        return $this->AuthResponse;
    }
}

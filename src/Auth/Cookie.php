<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Controllers\LoginController;
use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use Elabftw\Services\TeamsHelper;

/**
 * Authenticate with the cookie
 */
class Cookie implements AuthInterface
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
        $sql = 'SELECT `users`.`userid`, `users`.`mfa_secret`, `users`.`auth_service`
            FROM `users` WHERE `token` = :token LIMIT 1';
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

        // Force user to login again to activate MFA if it is enforced for local auth and there is no mfaSecret
        if ($res['auth_service'] === LoginController::AUTH_LOCAL
            && Local::enforceMfa($this->AuthResponse, (int) $this->configArr['enforce_mfa'])
        ) {
            throw new UnauthorizedException();
        }

        return $this->AuthResponse;
    }
}

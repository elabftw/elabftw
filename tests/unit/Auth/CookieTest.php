<?php

declare(strict_types=1);
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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\UnauthorizedException;
use PDO;

class CookieTest extends \PHPUnit\Framework\TestCase
{
    private Db $Db;

    private CookieToken $CookieToken;

    private int $userid = 1;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->CookieToken = CookieToken::fromScratch();
        $this->CookieToken->saveToken($this->userid);
    }

    public function testTryAuthExpired(): void
    {
        // cookie is valid only one minute
        $CookieAuth = new Cookie(1, 0, $this->CookieToken, 1);
        // create a token but 4 minutes in the past
        $req = $this->Db->prepare('UPDATE users SET token = :token, token_created_at = DATE_SUB(NOW(), INTERVAL 4 MINUTE) WHERE userid = :userid');
        $req->bindValue(':token', $this->CookieToken->getToken());
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->execute();
        // now try login but our cookie isn't valid anymore
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testTryAuthSuccess(): void
    {
        $CookieAuth = new Cookie(220330, 0, $this->CookieToken, 1);
        $res = $CookieAuth->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $res);
        $this->assertEquals(1, $res->userid);
        $this->assertEquals(1, $res->selectedTeam);
    }

    public function testTryAuthFail(): void
    {
        $token = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $CookieAuth = new Cookie(220330, 0, new CookieToken($token), 1);
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testTryAuthBadTeam(): void
    {
        $CookieAuth = new Cookie(220330, 0, $this->CookieToken, 2);
        $req = $this->Db->prepare('UPDATE users SET token = :token WHERE userid = :userid');
        $req->bindValue(':token', $this->CookieToken->getToken());
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->execute();
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testTryAuthEnforceMFA(): void
    {
        $CookieAuth = new Cookie(220330, 1, $this->CookieToken, 1);
        $req = $this->Db->prepare('UPDATE users SET token = :token, auth_service = :auth_service WHERE userid = :userid');
        $req->bindValue(':token', $this->CookieToken->getToken());
        $req->bindValue(':auth_service', LoginController::AUTH_LOCAL);
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $req->execute();
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testInvalidToken(): void
    {
        $this->expectException(IllegalActionException::class);
        new CookieToken('invalid length');
    }
}

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
        $CookieAuth = new Cookie(1, $this->CookieToken);
        // create a token but 4 minutes in the past
        $req = $this->Db->prepare('UPDATE users SET token = :token, token_created_at = DATE_SUB(NOW(), INTERVAL 4 MINUTE) WHERE userid = :userid');
        $req->bindValue(':token', $this->CookieToken->getToken());
        $req->bindParam(':userid', $this->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        // now try login but our cookie isn't valid anymore
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->authenticate();
    }

    public function testTryAuthSuccess(): void
    {
        $CookieAuth = new Cookie(220330, $this->CookieToken);
        $res = $CookieAuth->authenticate();
        $this->assertInstanceOf(Authentication::class, $res);
        $this->assertSame(1, $res->userid);
    }

    public function testTryAuthFail(): void
    {
        $token = 'deadbeefcafebabe0badf00ddecafbad';
        $CookieAuth = new Cookie(220330, new CookieToken($token));
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->authenticate();
    }

    public function testInvalidToken(): void
    {
        $CookieToken = new CookieToken('invalid length');
        $this->assertEmpty($CookieToken->getToken());
    }
}

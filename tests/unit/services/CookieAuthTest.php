<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\UnauthorizedException;

class CookieAuthTest extends \PHPUnit\Framework\TestCase
{
    public function testTryAuthSuccess(): void
    {
        $token = '8669b095961a14edc0dd37fefa76e932938b830f2d02377a8f2154cc3f12719d';
        $CookieAuth = new CookieAuth($token, '1');
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE users SET token = :token WHERE userid = 1');
        $req->bindParam(':token', $token);
        $req->execute();
        $res = $CookieAuth->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $res);
        $this->assertEquals('1', $res->userid);
        $this->assertEquals('1', $res->selectedTeam);
    }

    public function testTryAuthFail(): void
    {
        $token = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $CookieAuth = new CookieAuth($token, '1');
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testTryAuthBadTeam(): void
    {
        $token = '8669b095961a14edc0dd37fefa76e932938b830f2d02377a8f2154cc3f12719d';
        $CookieAuth = new CookieAuth($token, '2');
        $Db = Db::getConnection();
        $req = $Db->prepare('UPDATE users SET token = :token WHERE userid = 1');
        $req->bindParam(':token', $token);
        $req->execute();
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }
}

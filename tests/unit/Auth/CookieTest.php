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
use Elabftw\Enums\EnforceMfa;
use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;

class CookieTest extends \PHPUnit\Framework\TestCase
{
    private Config $Config;

    private Db $Db;

    private string $token;

    public static function tearDownAfterClass(): void
    {
        $req = (Db::getConnection())->prepare("UPDATE config SET conf_value = '0' WHERE conf_name = 'enforce_mfa'");
        $req->execute();
    }

    protected function setUp(): void
    {
        $this->Config = Config::getConfig();
        $this->Db = Db::getConnection();
        $this->token = '8669b095961a14edc0dd37fefa76e932938b830f2d02377a8f2154cc3f12719d';
    }

    public function testTryAuthSuccess(): void
    {
        $CookieAuth = new Cookie($this->token, '1', $this->Config->configArr);
        $req = $this->Db->prepare('UPDATE users SET token = :token WHERE userid = 1');
        $req->bindParam(':token', $this->token);
        $req->execute();
        $res = $CookieAuth->tryAuth();
        $this->assertInstanceOf(AuthResponse::class, $res);
        $this->assertEquals('1', $res->userid);
        $this->assertEquals('1', $res->selectedTeam);
    }

    public function testTryAuthFail(): void
    {
        $token = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $CookieAuth = new Cookie($token, '1', $this->Config->configArr);
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testTryAuthBadTeam(): void
    {
        $CookieAuth = new Cookie($this->token, '2', $this->Config->configArr);
        $req = $this->Db->prepare('UPDATE users SET token = :token WHERE userid = 1');
        $req->bindParam(':token', $this->token);
        $req->execute();
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }

    public function testTryAuthEnforceMFA(): void
    {
        $req = $this->Db->prepare('UPDATE users SET token = :token, auth_service = :auth_service WHERE userid = 1');
        $req->bindParam(':token', $this->token);
        $req->bindValue(':auth_service', LoginController::AUTH_LOCAL);
        $req->execute();
        $req = $this->Db->prepare("UPDATE config SET conf_value = :value WHERE conf_name = 'enforce_mfa'");
        $req->bindValue(':value', EnforceMfa::Everyone->value);
        $req->execute();
        $CookieAuth = new Cookie($this->token, '1', $this->Config->readAll());
        $this->expectException(UnauthorizedException::class);
        $CookieAuth->tryAuth();
    }
}

<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Usergroup;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Users;

class CheckTest extends \PHPUnit\Framework\TestCase
{
    public function testPasswordLength(): void
    {
        $this->assertIsString(Check::passwordLength('longpassword'));
        $this->expectException(ImproperActionException::class);
        Check::passwordLength('short');
    }

    public function testId(): void
    {
        $this->assertFalse(Check::id(-42));
        $this->assertFalse(Check::id(0));
        $this->assertEquals(3, Check::id((int) 3.1415926535));
        $this->assertEquals(42, Check::id(42));
    }

    public function testColor(): void
    {
        $this->assertEquals('AABBCC', Check::color('#AABBCC'));
        $this->expectException(ImproperActionException::class);
        Check::color('pwet');
    }

    public function testVisibility(): void
    {
        $this->assertEquals(BasePermissions::Team->toJson(), Check::visibility(BasePermissions::Team->toJson()));
        $this->expectException(ImproperActionException::class);
        Check::visibility('pwet');
    }

    public function testVisibilityIncorrectBase(): void
    {
        $this->expectException(ImproperActionException::class);
        Check::visibility('{"base": 12}');
    }

    public function testVisibilityIncorrectArray(): void
    {
        $this->expectException(ImproperActionException::class);
        Check::visibility('{"base": 10, "teams": "yep"}');
    }

    public function testAk(): void
    {
        $ak = '4da7ac2a-a3f0-11ed-bb95-0242ac160008';
        $this->assertEquals($ak, Check::accessKey($ak));
        $this->expectException(ImproperActionException::class);
        Check::accessKey('pwet');
    }

    public function testDigit(): void
    {
        $this->assertTrue(Check::digit('000000027494555', 5));
        $this->assertTrue(Check::digit('000000021825009', 7));
        $this->assertTrue(Check::digit('000000021694233', 10));
        $this->assertFalse(Check::digit('100000021825009', 7));
    }

    public function testUsergroup(): void
    {
        // simulate a non admin trying to set a usergroup level of admin
        $requester = new Users(3, 2);
        $usergroup = Usergroup::Admin;
        $this->assertEquals(Usergroup::User, Check::usergroup($requester, $usergroup));
    }
}

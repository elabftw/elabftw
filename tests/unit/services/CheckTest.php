<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use function bin2hex;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use function hash;
use function random_bytes;
use TypeError;

class CheckTest extends \PHPUnit\Framework\TestCase
{
    public function testPasswordLength(): void
    {
        $this->assertTrue(Check::passwordLength('longpassword'));
        $this->expectException(ImproperActionException::class);
        Check::passwordLength('short');
    }

    public function testId(): void
    {
        $this->expectException(TypeError::class);
        // @phpstan-ignore-next-line
        $this->assertFalse(Check::id('yep'));
        $this->assertFalse(Check::id(-42));
        $this->assertFalse(Check::id(0));
        $this->assertEquals(3, Check::id((int) 3.1415926535));
        $this->assertEquals(42, Check::id(42));
    }

    public function testIdOrExplode(): void
    {
        $this->expectException(IllegalActionException::class);
        Check::idOrExplode(-1337);
    }

    public function testColor(): void
    {
        $this->assertEquals('AABBCC', Check::color('#AABBCC'));
        $this->expectException(ImproperActionException::class);
        Check::color('pwet');
    }

    public function testVisibility(): void
    {
        $this->assertEquals('team', Check::visibility('team'));
        $this->expectException(IllegalActionException::class);
        Check::visibility('pwet');
    }

    public function testDisplaySize(): void
    {
        $this->assertEquals('lg', Check::displaySize('blah'));
        $this->assertEquals('xs', Check::displaySize('xs'));
        $this->assertEquals('md', Check::displaySize('md'));
    }

    public function testDisplayMode(): void
    {
        $this->assertEquals('it', Check::displayMode('blah'));
        $this->assertEquals('it', Check::displayMode('it'));
        $this->assertEquals('tb', Check::displayMode('tb'));
    }

    public function testOrderby(): void
    {
        $this->assertEquals('date', Check::orderby('date'));
        $this->expectException(ImproperActionException::class);
        Check::orderby('blah');
    }

    public function testSort(): void
    {
        $this->assertEquals('asc', Check::sort('asc'));
        $this->assertEquals('desc', Check::sort('desc'));
        $this->expectException(ImproperActionException::class);
        Check::sort('blah');
    }

    public function testRw(): void
    {
        $this->assertEquals('read', Check::rw('read'));
        $this->assertEquals('write', Check::rw('write'));
        $this->expectException(IllegalActionException::class);
        Check::rw('blah');
    }

    public function testToken(): void
    {
        $token = hash('sha256', bin2hex(random_bytes(16)));
        $this->assertEquals($token, Check::token($token));
        $this->expectException(IllegalActionException::class);
        Check::token('blah');
    }
}

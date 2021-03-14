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

class CheckTest extends \PHPUnit\Framework\TestCase
{
    public function testPasswordLength()
    {
        $this->assertTrue(Check::passwordLength('longpassword'));
        $this->expectException(ImproperActionException::class);
        Check::passwordLength('short');
    }

    public function testId()
    {
        $this->expectException(\TypeError::class);
        $this->assertFalse(Check::id('yep'));
        $this->assertFalse(Check::id(-42));
        $this->assertFalse(Check::id(0));
        $this->assertFalse(Check::id(3.1415926535));
        $this->assertEquals(42, Check::id(42));
    }

    public function testIdOrExplode()
    {
        $this->expectException(IllegalActionException::class);
        Check::idOrExplode(-1337);
    }

    public function testColor()
    {
        $this->assertEquals('AABBCC', Check::color('#AABBCC'));
        $this->expectException(ImproperActionException::class);
        Check::color('pwet');
    }

    public function testVisibility()
    {
        $this->assertEquals('team', Check::visibility('team'));
        $this->expectException(IllegalActionException::class);
        Check::visibility('pwet');
    }

    public function testDisplaySize()
    {
        $this->assertEquals('lg', Check::displaySize('blah'));
        $this->assertEquals('xs', Check::displaySize('xs'));
        $this->assertEquals('md', Check::displaySize('md'));
    }

    public function testDisplayMode()
    {
        $this->assertEquals('it', Check::displayMode('blah'));
        $this->assertEquals('it', Check::displayMode('it'));
        $this->assertEquals('tb', Check::displayMode('tb'));
    }

    public function testOrderby()
    {
        $this->assertEquals('date', Check::orderby('date'));
        $this->expectException(ImproperActionException::class);
        Check::orderby('blah');
    }

    public function testSort()
    {
        $this->assertEquals('asc', Check::sort('asc'));
        $this->assertEquals('desc', Check::sort('desc'));
        $this->expectException(ImproperActionException::class);
        Check::sort('blah');
    }

    public function testRw()
    {
        $this->assertEquals('read', Check::rw('read'));
        $this->assertEquals('write', Check::rw('write'));
        $this->expectException(IllegalActionException::class);
        Check::rw('blah');
    }

    public function testToken()
    {
        $token = hash('sha256', bin2hex(random_bytes(16)));
        $this->assertEquals($token, Check::token($token));
        $this->expectException(IllegalActionException::class);
        Check::token('blah');
    }
}

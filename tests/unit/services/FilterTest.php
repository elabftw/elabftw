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

use DateTimeImmutable;
use Elabftw\Exceptions\ImproperActionException;

use function str_repeat;

class FilterTest extends \PHPUnit\Framework\TestCase
{
    public function testKdate(): void
    {
        $this->assertEquals('1969-07-21', Filter::kdate('1969-07-21'));
        $this->assertEquals(date('Y-m-d'), Filter::kdate('3902348923'));
        $this->assertEquals(date('Y-m-d'), Filter::kdate('Sun is shining'));
        $this->assertEquals(date('Y-m-d'), Filter::kdate("\n"));
    }

    public function testFormatLocalDate(): void
    {
        $input = '2024-10-16 17:12:47';
        $expected = array(
            'date' => '2024-10-16',
            'time' => '17:12:47',
        );
        $this->assertEquals($expected, Filter::separateDateAndTime($input));

        $input = '2024-10-16';
        $expected = array(
            'date' => '2024-10-16',
            'time' => '',
        );
        $this->assertEquals($expected, Filter::separateDateAndTime($input));

        $input = '';
        $expected = array(
            'date' => '',
            'time' => '',
        );
        $this->assertEquals($expected, Filter::separateDateAndTime($input));
        $this->assertSame('Monday, July 14, 2025', Filter::formatLocalDate(new DateTimeImmutable('2025-07-14')));
    }

    public function testTitle(): void
    {
        $this->assertEquals('My super title', Filter::title('My super title'));
        $this->assertEquals('Yep Yop Yip Yup', Filter::title("Yep\r\nYop\nYip\rYup"));
        $this->assertEquals('Untitled', Filter::title(''));
        $this->assertEquals('Untitled', Filter::title(' '));
        $this->assertEquals('no whitespace around', Filter::title(' no whitespace around '));
        // test a too long string
        $this->assertEquals(str_repeat('A', 255), Filter::title(str_repeat('A', 260)));
    }

    public function testBody(): void
    {
        $this->assertEquals('my body', Filter::body('my body'));
        $this->assertEquals('my body', Filter::body('my body<script></script>'));
        $this->expectException(ImproperActionException::class);
        Filter::body(str_repeat('a', 4120001));
    }

    public function testForFilesystem(): void
    {
        $this->assertEquals('blah', Filter::forFilesystem('=blah/'));
        $this->assertEquals('.pdf', Filter::forFilesystem("=bl사회과학원 어 학연구소찦차를 타고 온 펲시맨과 쑛다리 똠방각하η†ah/'\n.pdf"));
        $this->assertEquals('23MJ.gif_th.jpg', Filter::forFilesystem('|23MJ.gif_th.jpg'));
    }

    public function testHexits(): void
    {
        // we use uniqid here so it changes every time
        $input = hash('sha512', uniqid('', true));
        $this->assertEquals($input, Filter::hexits($input));
        $this->assertEquals('abc', Filter::hexits('zzzazzzbzzzczzz'));
        $this->assertEmpty(Filter::hexits('zzzzz'));
    }

    public function testToPureString(): void
    {
        $this->assertEquals('Roger', Filter::toPureString('<a href="attacker.com">Roger</a>'));
        $this->assertEquals('Roger', Filter::toPureString('<script>alert(1)</script><strong>Roger</strong>'));
        $this->assertEquals('Rabbit', Filter::toPureString('<i onwheel=alert(224)>Rabbit</i>'));
    }

    public function testIntOrNull(): void
    {
        $this->assertNull(Filter::intOrNull(''));
        $this->assertSame(42, Filter::intOrNull('42'));
    }
}

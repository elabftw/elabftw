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
    }

    public function testTitle(): void
    {
        $this->assertEquals('My super title', Filter::title('My super title'));
        $this->assertEquals('Yep Yop Yip Yup', Filter::title("Yep\r\nYop\nYip\rYup"));
        $this->assertEquals('Untitled', Filter::title(''));
        $this->assertEquals('Untitled', Filter::title(' '));
        $this->assertEquals('no whitespace around', Filter::title(' no whitespace around '));
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
    }

    public function testHexits(): void
    {
        // we use uniqid here so it changes every time
        $input = hash('sha512', uniqid('', true));
        $this->assertEquals($input, Filter::hexits($input));
        $this->assertEquals('abc', Filter::hexits('zzzazzzbzzzczzz'));
        $this->assertEmpty(Filter::hexits('zzzzz'));
    }
}

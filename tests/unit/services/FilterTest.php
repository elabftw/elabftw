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
use Elabftw\Traits\TestsUtilsTrait;

use function str_repeat;
use function hash;
use function uniqid;

class FilterTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

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

    public function testToBinary(): void
    {
        $this->assertSame(0, Filter::toBinary('off'));
        $this->assertSame(1, Filter::toBinary('yep'));
    }

    public function testTruncateString(): void
    {
        $this->assertSame(str_repeat('🧪', 12), Filter::truncateStringToChars(str_repeat('🧪', 13), 12));
        $this->assertSame('', Filter::truncateString(''));
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Error reducing string to size 12!');
        Filter::truncateStringToChars("\xFF", 12);
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

    public function testTitleLimitsCodePointsWithoutSplittingGraphemes(): void
    {
        $this->assertSame(str_repeat('🧪', 255), Filter::title(str_repeat('🧪', 256)));
        $this->assertSame(str_repeat('👩🏽‍🔬', 63), Filter::title(str_repeat('👩🏽‍🔬', 100)));
    }

    public function testStepPreservesLongBodies(): void
    {
        $this->assertSame(str_repeat("か\u{3099}", 1000), Filter::step(str_repeat("か\u{3099}", 1000)));
        $this->assertSame(str_repeat('👩🏽‍🔬', 1000), Filter::step(str_repeat('👩🏽‍🔬', 1000)));
        $this->assertSame(str_repeat('A', 1000), Filter::step(str_repeat('A', 1000)));
        $this->assertSame('Untitled', Filter::step(' '));
        $this->assertSame('First Second Third Fourth', Filter::step(" First\r\nSecond\nThird\rFourth "));
    }

    public function testStepAcceptsTextByteLimit(): void
    {
        $ascii = str_repeat('A', 65535);
        $unicode = str_repeat('🧪', 16383) . 'end';
        $this->assertSame($ascii, Filter::step($ascii));
        $this->assertSame($unicode, Filter::step($unicode));
    }

    public function testStepNormalizesBeforeCheckingSize(): void
    {
        $body = str_repeat('A', 65535);
        $this->assertSame($body, Filter::step(" \r\n" . $body . "\r\n "));
        $this->assertSame('Head ' . str_repeat('A', 65530), Filter::step("Head\r\n" . str_repeat('A', 65530)));
    }

    public function testStepRejectsAsciiBodyOverTextByteLimit(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Step is too long! (maximum: 65535 bytes)');
        Filter::step(str_repeat('A', 65536));
    }

    public function testStepRejectsUnicodeBodyOverTextByteLimit(): void
    {
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('Step is too long! (maximum: 65535 bytes)');
        Filter::step(str_repeat('🧪', 16384));
    }

    public function testBody(): void
    {
        $this->assertEquals('my body', Filter::body('my body'));
        $this->assertEquals('my body', Filter::body('my body<script></script>'));
        $this->expectException(ImproperActionException::class);
        Filter::body(str_repeat('a', 4120001));
    }

    public function testBodyMarkdown(): void
    {
        $this->assertSame('H & \\mathbf{1}', Filter::bodyMarkdown('H & \\mathbf{1}'));
        $this->expectException(ImproperActionException::class);
        Filter::bodyMarkdown(str_repeat('a', 4120001));
    }

    public function testBodyAllowsInternalLinksToOpenInNewWindow(): void
    {
        $link = '<a href="/experiments/1" target="_blank" rel="noreferrer noopener">Experiment</a>';
        $this->assertSame($link, Filter::body($link));

        $this->assertSame(
            '<a href="/experiments/1">Experiment</a>',
            Filter::body('<a href="/experiments/1" target="_top">Experiment</a>'),
        );
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

    public function testBodyPreservesTinyMceAccordionClass(): void
    {
        $input = '<details class="mce-accordion"><summary>Summary</summary><p>One</p><p>Two</p></details>';
        $this->assertSame($input, Filter::body($input));
    }

    public function testToFsTitleSanitizesCategory(): void
    {
        $Entity = $this->getFreshExperiment();
        $Entity->entityData['category_title'] = 'Category 07/10/2026';
        $this->assertStringStartsWith('Category-07-10-2026 - ', $Entity->toFsTitle());
    }

    public function testToAsciiSlug(): void
    {
        $this->assertEquals('test-export-07-10-2026', Filter::toAsciiSlug('test export 07/10/2026'));
        $this->assertEquals('From-07-10-2026-to-10-10-2026', Filter::toAsciiSlug('From 07/10/2026 to 10/10/2026'));
    }

    public function testForFilesystemTitlePreservesUnicode(): void
    {
        $this->assertSame('研究---Study', Filter::forFilesystemTitle('研究 / Study'));
        $this->assertSame('한국어-제목---中文标题', Filter::forFilesystemTitle('한국어 제목 / 中文标题'));
        $this->assertSame('Étude-expérimentale', Filter::forFilesystemTitle('Étude expérimentale'));
    }

    public function testForFilesystemTitlePreservesEmojis(): void
    {
        $this->assertSame('Analyse-🧪', Filter::forFilesystemTitle('Analyse 🧪'));
        $this->assertSame('Chercheuse-👩🏽‍🔬', Filter::forFilesystemTitle('Chercheuse 👩🏽‍🔬'));
        $this->assertSame('🧪', Filter::forFilesystemTitle('🧪'));
    }

    public function testForFilesystemTitleLimitsBytesAfterNormalization(): void
    {
        // U+0958 normalizes to U+0915 + U+093C, growing from three to six bytes.
        $this->assertSame(str_repeat("\u{0915}\u{093C}", 16), Filter::forFilesystemTitle(str_repeat("\u{0958}", 33)));
        // Normalization can also shrink a title that fits only after composition.
        $this->assertSame(str_repeat('é', 50), Filter::forFilesystemTitle(str_repeat("e\u{0301}", 50)));
    }

    public function testForFilesystemTitleTruncatesAtGraphemeBoundaries(): void
    {
        $this->assertSame(str_repeat('A', 100), Filter::forFilesystemTitle(str_repeat('A', 101)));
        $this->assertSame(str_repeat('🧪', 25), Filter::forFilesystemTitle(str_repeat('🧪', 26)));
        $this->assertSame(str_repeat('👩🏽‍🔬', 6), Filter::forFilesystemTitle(str_repeat('👩🏽‍🔬', 7)));
    }

    public function testForFilesystemTitleHandlesEmptyResult(): void
    {
        $this->assertSame('Untitled', Filter::forFilesystemTitle('///'));
        $this->assertSame('Untitled', Filter::forFilesystemTitle('/,.\\'));
        $this->assertSame('Untitled', Filter::forFilesystemTitle(str_repeat('.', 100) . 'Study'));
        $this->assertSame('Untitled', Filter::forFilesystemTitle('x' . str_repeat("\u{0301}", 100)));
        $this->assertSame('Study', Filter::forFilesystemTitle(str_repeat('/', 100) . 'Study'));
    }

    public function testFirstLetter(): void
    {
        $this->assertSame('y', Filter::firstLetter('yo'));
        $this->expectException(ImproperActionException::class);
        Filter::firstLetter('');
    }
}

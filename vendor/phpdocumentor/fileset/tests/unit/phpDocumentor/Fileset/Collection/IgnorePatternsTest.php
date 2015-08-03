<?php
/**
 * phpDocumentor
 *
 * PHP Version 5
 *
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2011 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Fileset\Collection;

/**
 * Test case for IgnorePatterns class.
 *
 * @author  Mike van Riel <mike.vanriel@naenius.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    http://phpdoc.org
 */
class IgnorePatternsTest extends \PHPUnit_Framework_TestCase
{
    /** @var IgnorePatterns */
    protected $fixture = null;

    /** @var bool */
    protected $isWindows = false;

    protected function setUp()
    {
        $this->fixture = new IgnorePatterns();
        if ('win' === strtolower(substr(PHP_OS, 0, 3))) {
            $this->isWindows = true;
        }
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::getRegularExpression() */
    public function testGetRegularExpressionWhenGivenNoPatternsReturnsNothing()
    {
        $this->assertEquals('', $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::getRegularExpression() */
    public function testGetRegularExpressionWhenGivenOnePatternsReturnsOneString()
    {
        $this->fixture->append('*r/');
        $expected = ($this->isWindows)
            ? '/((?:.*\\\\.*r\\\?.*|.*r\\\\.*))$/'
            : '/((?:.*\/.*r\/?.*|.*r\/.*))$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::convertToPregCompliant() */
    public function testtestConvertToPregCompliantForGlobbedDir()
    {
        $this->fixture->append('*r/');
        $expected = ($this->isWindows)
            ? '/((?:.*\\\\.*r\\\?.*|.*r\\\\.*))$/'
            : '/((?:.*\/.*r\/?.*|.*r\/.*))$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::convertToPregCompliant() */
    public function testConvertToPregCompliantForFilenameMask()
    {
        $this->fixture->append('Fileset*');
        $expected = ($this->isWindows)
            ? '/(Fileset.*)$/'
            : '/(Fileset.*)$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::convertToPregCompliant() */
    public function testConvertToPregCompliantForFilenameMaskDotExtension()
    {
        $this->fixture->append('Fileset*.php');
        $expected = ($this->isWindows)
            ? '/(Fileset.*\.php)$/'
            : '/(Fileset.*\.php)$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::convertToPregCompliant() */
    public function testConvertToPregCompliantForGlobbedDirAndFilenameMaskDotExtension()
    {
        $this->fixture->append('*r/Fileset*.php');
        $expected = ($this->isWindows)
            ? '/(.*r\\\\Fileset.*\.php)$/'
            : '/(.*r\/Fileset.*\.php)$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::convertToPregCompliant() */
    public function testConvertToPregCompliantForThreeStars()
    {
        $this->fixture->append('***');
        $expected = ($this->isWindows)
            ? '/(.*.*.*)$/'
            : '/(.*.*.*)$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }

    /** @covers \phpDocumentor\Fileset\Collection\IgnorePatterns::convertToPregCompliant() */
    public function testConvertToPregCompliantForPlainString()
    {
        $this->fixture->append('plainString');
        $expected = ($this->isWindows)
            ? '/(plainString)$/'
            : '/(plainString)$/'
        ;
        $this->assertEquals($expected, $this->fixture->getRegularExpression());
    }
}

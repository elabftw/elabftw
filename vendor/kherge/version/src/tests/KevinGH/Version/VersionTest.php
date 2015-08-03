<?php

/* This file is part of Version.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Version;

use PHPUnit_Framework_TestCase;

class VersionTest extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf('KevinGH\Version\Version', $version = Version::create('1.0.0'));

        $this->assertEquals(1, $version->getMajor());
        $this->assertEquals(0, $version->getMinor());
        $this->assertEquals(0, $version->getPatch());
        $this->assertNull($version->getPreRelease());
        $this->assertNull($version->getBuild());
    }

    /**
     * @dataProvider getGoodVersions
     */
    public function testIsValid($string)
    {
        $this->assertTrue(Version::isValid($string));
    }

    /**
     * @dataProvider getBadVersions
     */
    public function testIsValidInvalid($string)
    {
        $this->assertFalse(Version::isValid($string));
    }

    public function testDefaults()
    {
        $version = new Version;

        $this->assertEquals(0, $version->getMajor());
        $this->assertEquals(0, $version->getMinor());
        $this->assertEquals(0, $version->getPatch());
        $this->assertNull($version->getPreRelease());
        $this->assertNull($version->getBuild());
    }

    /**
     * @dataProvider getParseDataSet
     */
    public function testParse($input, $expected)
    {
        $version = new Version($input);

        $this->assertSame($expected['major'], $version->getMajor());
        $this->assertSame($expected['minor'], $version->getMinor());
        $this->assertSame($expected['patch'], $version->getPatch());
        $this->assertSame($expected['pre'], $version->getPreRelease());
        $this->assertSame($expected['build'], $version->getBuild());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The version string "a.0.0" is invalid.
     */
    public function testParseInvalid()
    {
        $version = new Version('a.0.0');
    }

    /**
     * @dataProvider getCompareDataSet
     */
    public function testCompare($left, $right, $result)
    {
        $left = new Version($left);
        $right = new Version($right);

        $this->assertSame($result, $left->compareTo($right));
    }

    /**
     * @dataProvider getEqualDataSet
     */
    public function testEqualTo($left, $right, $result)
    {
        $left = new Version($left);
        $right = new Version($right);

        $this->assertTrue($left->isEqualTo($right));
    }

    /**
     * @dataProvider getGreaterDataSet
     */
    public function testGreaterThan($left, $right, $reuslt)
    {
        $left = new Version($left);
        $right = new Version($right);

        $this->assertTrue($left->isGreaterThan($right));
    }

    /**
     * @dataProvider getLessDataSet
     */
    public function testLessThan($left, $right, $reuslt)
    {
        $left = new Version($left);
        $right = new Version($right);

        $this->assertTrue($left->isLessThan($right));
    }

    /**
     * @dataProvider getGoodVersions
     */
    public function testStable($version)
    {
        $stable = !(bool) strpos($version, '-');

        $version = new Version($version);

        $this->assertSame($stable, $version->isStable());
    }

    /**
     * @depends testDefaults
     */
    public function testSetMajor()
    {
        $version = new Version;

        $version->setMajor(1);

        $this->assertSame(1, $version->getMajor());
    }

    /**
     * @depends testDefaults
     */
    public function testSetMinor()
    {
        $version = new Version;

        $version->setMinor(1);

        $this->assertSame(1, $version->getMinor());
    }

    /**
     * @depends testDefaults
     */
    public function testSetPatch()
    {
        $version = new Version;

        $version->setPatch(1);

        $this->assertSame(1, $version->getPatch());
    }

    public function getBadVersions()
    {
        return array(
            array('x.0.0-0.x.1.b'),
            array('0.0.0-'),
            array('0.0.0-1.'),
            array('1.x.0-0.x.1.b+build.123.abcdef1'),
            array('1.0.x-alpha'),
            array('1.0.0-!.1'),
            array('1.0.0-beta!2'),
            array('1.0.0-rc.1!build.1'),
            array('1.0.0+'),
            array('1.0.0!0.3.7'),
            array('1.3.7!build'),
            array('1.3.7+build.1.'),
            array('1.3.7+build.11!e0f985a')
        );
    }

    public function getCompareDataSet()
    {
        return array_merge($this->getLessDataSet(), $this->getGreaterDataSet(), $this->getEqualDataSet());
    }

    public function getEqualDataSet()
    {
        return array(
            array(
                '0.0.0-alpha',
                '0.0.0-alpha',
                0
            ),
            array(
                '0.1.0-alpha',
                '0.1.0-alpha',
                0
            ),
            array(
                '1.0.0-alpha',
                '1.0.0-alpha',
                0
            ),
            array(
                '1.0.0-alpha.1',
                '1.0.0-alpha.1',
                0
            ),
            array(
                '1.0.0-beta.2',
                '1.0.0-beta.2',
                0
            ),
            array(
                '1.0.0-beta.11',
                '1.0.0-beta.11',
                0
            ),
            array(
                '1.0.0-rc.1',
                '1.0.0-rc.1',
                0
            ),
            array(
                '1.0.0-rc.1+build.1',
                '1.0.0-rc.1+build.1',
                0
            ),
            array(
                '1.0.0',
                '1.0.0',
                0
            ),
            array(
                '1.0.0+0.3.7',
                '1.0.0+0.3.7',
                0
            ),
            array(
                '1.3.7+build',
                '1.3.7+build',
                0
            ),
            array(
                '1.3.7+build.2.b8f12d7',
                '1.3.7+build.2.b8f12d7',
                0
            ),
            array(
                '1.3.7+build.11.e0f985a',
                '1.3.7+build.11.e0f985a',
                0
            )
        );
    }

    public function getLessDataSet()
    {
        return array(
            array(
                '0.1.0-alpha.1',
                '0.1.1-alpha.1',
                1
            ),
            array(
                '0.1.0-alpha.1',
                '1.0.0-alpha.1',
                1
            ),
            array(
                '1.0.0-alpha',
                '1.0.0-alpha.1',
                1
            ),
            array(
                '1.0.0-alpha.1',
                '1.0.0-alpha.1.1',
                1
            ),
            array(
                '1.0.0-alpha.1',
                '1.0.0-beta.2',
                1
            ),
            array(
                '1.0.0-beta.2',
                '1.0.0-beta.11',
                1
            ),
            array(
                '1.0.0-beta.11',
                '1.0.0-rc.1',
                1
            ),
            array(
                '1.0.0-rc.1',
                '1.0.0-rc.1+build.1',
                1
            ),
            array(
                '1.0.0-rc.1+build.1',
                '1.0.0',
                1
            ),
            array(
                '1.0.0',
                '1.0.0+0.3.7',
                1
            ),
            array(
                '1.0.0+0.3.7',
                '1.3.7+build',
                1
            ),
            array(
                '1.3.7+build',
                '1.3.7+build.2.b8f12d7',
                1
            ),
            array(
                '1.3.7+build.2.b8f12d7',
                '1.3.7+build.11.e0f985a',
                1
            ),
            array(
                '1.3.7+1',
                '1.3.7+build.2',
                1
            ),
        );
    }

    public function getGoodVersions()
    {
        return array(
            array('1.0.0-0.x.1.b'),
            array('1.0.0-0.x.1.b+build.123.abcdef1'),
            array('1.0.0-alpha'),
            array('1.0.0-alpha.1'),
            array('1.0.0-beta.2'),
            array('1.0.0-beta.11'),
            array('1.0.0-rc.1'),
            array('1.0.0-rc.1+build.1'),
            array('1.0.0'),
            array('1.0.0+0.3.7'),
            array('1.3.7+build'),
            array('1.3.7+build.2.b8f12d7'),
            array('1.3.7+build.11.e0f985a')
        );
    }

    public function getGreaterDataSet()
    {
        return array(
            array(
                '0.1.1-alpha.1',
                '0.1.0-alpha.1',
                -1
            ),
            array(
                '1.0.0-alpha.1',
                '0.1.0-alpha.1',
                -1
            ),
            array(
                '1.0.0-alpha.1',
                '1.0.0-alpha',
                -1
            ),
            array(
                '1.0.0-beta.2',
                '1.0.0-alpha.1',
                -1
            ),
            array(
                '1.0.0-beta.11',
                '1.0.0-beta.2',
                -1
            ),
            array(
                '1.0.0-rc.1',
                '1.0.0-beta.11',
                -1
            ),
            array(
                '1.0.0-rc.1+build.1',
                '1.0.0-rc.1',
                -1
            ),
            array(
                '1.0.0',
                '1.0.0-rc.1+build.1',
                -1
            ),
            array(
                '1.0.0+0.3.7',
                '1.0.0',
                -1
            ),
            array(
                '1.3.7+build',
                '1.0.0+0.3.7',
                -1
            ),
            array(
                '1.3.7+build.2.b8f12d7',
                '1.3.7+build',
                -1
            ),
            array(
                '1.3.7+build.11.e0f985a',
                '1.3.7+build.2.b8f12d7',
                -1
            ),
            array(
                '1.3.7+build.2',
                '1.3.7+1',
                -1
            ),
        );
    }

    public function getParseDataSet()
    {
        return array(
            array(
                '1.0.0-0.x.1.b',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        0,
                        'x',
                        1,
                        'b'
                    ),
                    'build' => null
                )
            ),
            array(
                '1.0.0-0.x.1.b+build.123.abcdef1',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        0,
                        'x',
                        1,
                        'b'
                    ),
                    'build' => array(
                        'build',
                        123,
                        'abcdef1'
                    )
                )
            ),
            array(
                '1.0.0-alpha',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array('alpha'),
                    'build' => null
                )
            ),
            array(
                '1.0.0-alpha.1',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        'alpha',
                        1
                    ),
                    'build' => null
                )
            ),
            array(
                '1.0.0-beta.2',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        'beta',
                        2
                    ),
                    'build' => null
                )
            ),
            array(
                '1.0.0-beta.11',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        'beta',
                        11
                    ),
                    'build' => null
                )
            ),
            array(
                '1.0.0-rc.1',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        'rc',
                        1
                    ),
                    'build' => null
                )
            ),
            array(
                '1.0.0-rc.1+build.1',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => array(
                        'rc',
                        1
                    ),
                    'build' => array(
                        'build',
                        1
                    )
                )
            ),
            array(
                '1.0.0',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => null,
                    'build' => null
                )
            ),
            array(
                '1.0.0+0.3.7',
                array(
                    'major' => 1,
                    'minor' => 0,
                    'patch' => 0,
                    'pre' => null,
                    'build' => array(
                        0,
                        3,
                        7
                    )
                )
            ),
            array(
                '1.3.7+build',
                array(
                    'major' => 1,
                    'minor' => 3,
                    'patch' => 7,
                    'pre' => null,
                    'build' => array('build')
                )
            ),
            array(
                '1.3.7+build.2.b8f12d7',
                array(
                    'major' => 1,
                    'minor' => 3,
                    'patch' => 7,
                    'pre' => null,
                    'build' => array(
                        'build',
                        2,
                        'b8f12d7'
                    )
                )
            ),
            array(
                '1.3.7+build.11.e0f985a',
                array(
                    'major' => 1,
                    'minor' => 3,
                    'patch' => 7,
                    'pre' => null,
                    'build' => array(
                        'build',
                        11,
                        'e0f985a'
                    )
                )
            )
        );
    }
}


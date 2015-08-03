<?php
/**
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Base
 * @subpackage Tests
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
/**
 * @package Base
 * @subpackage Tests
 */
class ezcBaseMetaDataPearTest extends ezcTestCase
{
    public function setUp()
    {
        $this->markTestSkipped('Only work when Zeta Components is installed as pear package.');
    }

    public static function testConstruct()
    {
        $r = new ezcBaseMetaData( 'pear' );
        self::assertInstanceOf( 'ezcBaseMetaData', $r );
        self::assertInstanceOf( 'ezcBaseMetaDataPearReader', self::readAttribute( $r, 'reader' ) );
    }

    public static function testGetBundleVersion()
    {
        $r = new ezcBaseMetaData( 'pear' );
        $release = $r->getBundleVersion();
        self::assertInternalType( 'string', $release );
        self::assertRegexp( '@[0-9]{4}\.[0-9](\.[0-9])?@', $release );
    }

    public static function testIsComponentInstalled()
    {
        $r = new ezcBaseMetaData( 'pear' );
        self::assertTrue( $r->isComponentInstalled( 'Base' ) );
        self::assertFalse( $r->isComponentInstalled( 'DefinitelyNot' ) );
    }

    public static function testGetComponentVersion()
    {
        $r = new ezcBaseMetaData( 'pear' );
        $release = $r->getComponentVersion( 'Base' );
        self::assertInternalType( 'string', $release );
        self::assertRegexp( '@[0-9]\.[0-9](\.[0-9])?@', $release );
        self::assertFalse( $r->getComponentVersion( 'DefinitelyNot' ) );
    }

    public static function testGetComponentDependencies1()
    {
        $r = new ezcBaseMetaData( 'pear' );
        $deps = array_keys( $r->getComponentDependencies() );
        self::assertContains( 'Base', $deps );
        self::assertContains( 'Cache', $deps );
        self::assertContains( 'Webdav', $deps );
        self::assertNotContains( 'Random', $deps );
    }

    public static function testGetComponentDependencies2()
    {
        $r = new ezcBaseMetaData( 'pear' );
        self::assertSame( array(), $r->getComponentDependencies( 'Base' ) );
        self::assertSame( array( 'Base' ), array_keys( $r->getComponentDependencies( 'Template' ) ) );
    }

    public static function testGetComponentDependencies3()
    {
        $r = new ezcBaseMetaData( 'pear' );
        self::assertContains( 'Base', array_keys( $r->getComponentDependencies( 'TemplateTranslationTiein' ) ) );
        self::assertContains( 'Template', array_keys( $r->getComponentDependencies( 'TemplateTranslationTiein' ) ) );
        self::assertContains( 'Translation', array_keys( $r->getComponentDependencies( 'TemplateTranslationTiein' ) ) );
    }

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( 'ezcBaseMetaDataPearTest' );
    }
}
?>

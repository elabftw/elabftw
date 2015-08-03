<?php
/**
 * ezcDocumentPdfDriverHaruTests
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
 * @package Document
 * @version //autogen//
 * @subpackage Tests
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

require_once 'driver_tests.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfListItemGeneratorTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getNumericGeneratorValues()
    {
        return array(
            array( 1, '1' ),
            array( 23, '23' ),
        );
    }

    /**
     * @dataProvider getNumericGeneratorValues
     */
    public function testNumericGenerator( $number, $item )
    {
        $generator = new ezcDocumentNumberedListItemGenerator();
        $this->assertSame(
            $item,
            $generator->getListItem( $number )
        );
    }

    public static function getAlphaGeneratorValues()
    {
        return array(
            array( 1, 'a' ),
            array( 23, 'w' ),
            array( 27, 'aa' ),
        );
    }

    /**
     * @dataProvider getAlphaGeneratorValues
     */
    public function testAlphaGenerator( $number, $item )
    {
        $generator = new ezcDocumentAlphaListItemGenerator();
        $this->assertSame(
            $item,
            $generator->getListItem( $number )
        );
    }

    /**
     * @dataProvider getAlphaGeneratorValues
     */
    public function testAlphaUpperGenerator( $number, $item )
    {
        $generator = new ezcDocumentAlphaListItemGenerator( ezcDocumentAlphaListItemGenerator::UPPER );
        $this->assertSame(
            strtoupper( $item ),
            $generator->getListItem( $number )
        );
    }

    public static function getRomanGeneratorValues()
    {
        return array(
            array( 1, 'i' ),
            array( 3, 'iii' ),
            array( 18, 'xviii' ),
            array( 3999, 'mmmcmxcix' ),
            array( 5999, 'mmmmmcmxcix' ),
        );
    }

    /**
     * @dataProvider getRomanGeneratorValues
     */
    public function testRomanGenerator( $number, $item )
    {
        $generator = new ezcDocumentRomanListItemGenerator();
        $this->assertSame(
            $item,
            $generator->getListItem( $number )
        );
    }

    /**
     * @dataProvider getRomanGeneratorValues
     */
    public function testRomanUpperGenerator( $number, $item )
    {
        $generator = new ezcDocumentRomanListItemGenerator( ezcDocumentRomanListItemGenerator::UPPER );
        $this->assertSame(
            strtoupper( $item ),
            $generator->getListItem( $number )
        );
    }

    public function testBulletGenerator()
    {
        $generator = new ezcDocumentBulletListItemGenerator();
        $this->assertSame( '-', $generator->getListItem( 23 ) );
        $this->assertSame( '-', $generator->getListItem( 42 ) );
    }

    public function testBulletGeneratorCusstomChar()
    {
        $generator = new ezcDocumentBulletListItemGenerator( '>' );
        $this->assertSame( '>', $generator->getListItem( 23 ) );
        $this->assertSame( '>', $generator->getListItem( 42 ) );
    }
}

?>

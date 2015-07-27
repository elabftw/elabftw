<?php
/**
 * ezcDocumentOdtFormattingPropertiesTest.
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

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentOdtStyleExtractorTest extends ezcTestCase
{
    protected $domDocument;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function setUp()
    {
        $this->domDocument = new DOMDocument();
        $this->domDocument->load( dirname( __FILE__ ) . '/../files/odt/tests/s_000_simple.fodt' );
    }
    
    public function testCtor()
    {
        $extr = $this->getExtractorFixture();

        $this->assertAttributeSame(
            $this->domDocument,
            'odt',
            $extr
        );

        $this->assertInstanceOf(
            'DOMXpath',
            $this->readAttribute( $extr, 'xpath' )
        );
    }

    public function testExtractStyleSuccess()
    {
        $extr = $this->getExtractorFixture();

        $style = $extr->extractStyle( 'paragraph', 'Text_20_body' );

        $this->assertInstanceOf(
            'DOMElement',
            $style
        );
        $this->assertEquals(
            'style',
            $style->localName
        );
        $this->assertEquals(
            'Text_20_body',
            $style->getAttributeNS(
                ezcDocumentOdt::NS_ODT_STYLE,
                'name'
            )
        );
    }

    public function testExtractDefaultStyleSuccess()
    {
        $extr = $this->getExtractorFixture();

        $style = $extr->extractStyle( 'paragraph' );

        $this->assertInstanceOf(
            'DOMElement',
            $style
        );
        $this->assertEquals(
            'default-style',
            $style->localName
        );
        $this->assertFalse(
            $style->hasAttributeNs(
                ezcDocumentOdt::NS_ODT_STYLE,
                'name'
            )
        );
        $this->assertEquals(
            'paragraph',
            $style->getAttributeNs(
                ezcDocumentOdt::NS_ODT_STYLE,
                'family'
            )
        );
    }

    public function testExtractStyleFailure()
    {
        $extr = $this->getExtractorFixture();

        try
        {
            $extr->extractStyle( 'paragraph', 'foobar' );
            $this->fail( 'Exception not thrown on extraction of non-existent style.' );
        }
        catch ( RuntimeException $e ) {}
    }

    public function testExtractDefaultStyleFailure()
    {
        $extr = $this->getExtractorFixture();

        try
        {
            $extr->extractStyle( 'foobar' );
            $this->fail( 'Exception not thrown on extraction of non-existent default style.' );
        }
        catch ( RuntimeException $e ) {}
    }

    public function testExtractListStyleSuccess()
    {
        $extr = $this->getExtractorFixture();

        $style = $extr->extractListStyle( 'L2' );

        $this->assertEquals(
            'list-style',
            $style->localName
        );
        $this->assertEquals(
            'L2',
            $style->getAttributeNS(
                ezcDocumentOdt::NS_ODT_STYLE,
                'name'
            )
        );
    }

    public function testExtractListStyleFailure()
    {
        $extr = $this->getExtractorFixture();

        try
        {
            $extr->extractListStyle( 'foobar' );
            $this->fail( 'Exception not thrown on extraction of non-existent list style.' );
        }
        catch ( RuntimeException $e ) {}
    }

    protected function getExtractorFixture()
    {
        return new ezcDocumentOdtStyleExtractor( $this->domDocument );
    }
}

?>

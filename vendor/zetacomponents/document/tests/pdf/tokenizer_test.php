<?php
/**
 * ezcDocumentPdfHyphenatorTests
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
class ezcDocumentPdfTokenizerTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testDefaultTokenizerNoSplit()
    {
        $hyphenator = new ezcDocumentPdfDefaultTokenizer();
        $this->assertSame(
            array( 'foo' ),
            $hyphenator->tokenize( 'foo' )
        );
    }

    public function testDefaultTokenizerSingleMiddleSplit()
    {
        $hyphenator = new ezcDocumentPdfDefaultTokenizer();
        $this->assertSame(
            array( 'foo', ezcDocumentPdfTokenizer::SPACE, 'bar' ),
            $hyphenator->tokenize( 'foo bar' )
        );
    }

    public function testDefaultTokenizerSplitAll()
    {
        $hyphenator = new ezcDocumentPdfDefaultTokenizer();
        $this->assertSame(
            array( ezcDocumentPdfTokenizer::SPACE, 'Hello', ezcDocumentPdfTokenizer::SPACE, 'world!', ezcDocumentPdfTokenizer::SPACE ),
            $hyphenator->tokenize( ' Hello world! ' )
        );
    }

    public function testDefaultTokenizerSplitTab()
    {
        $hyphenator = new ezcDocumentPdfDefaultTokenizer();
        $this->assertSame(
            array( 'foo', ezcDocumentPdfTokenizer::SPACE, 'bar' ),
            $hyphenator->tokenize( "foo\tbar" )
        );
    }

    public function testDefaultTokenizerSplitNewLine()
    {
        $hyphenator = new ezcDocumentPdfDefaultTokenizer();
        $this->assertSame(
            array( 'foo', ezcDocumentPdfTokenizer::SPACE, 'bar' ),
            $hyphenator->tokenize( "foo\tbar" )
        );
    }

    public function testDefaultTokenizerSplitMultipleDifferentSpaces()
    {
        $hyphenator = new ezcDocumentPdfDefaultTokenizer();
        $this->assertSame(
            array( 'foo', ezcDocumentPdfTokenizer::SPACE, 'bar' ),
            $hyphenator->tokenize( "foo \t \r \n  bar" )
        );
    }
}
?>

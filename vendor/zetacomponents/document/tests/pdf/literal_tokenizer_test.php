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
class ezcDocumentPdfLiteralTokenizerTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testDefaultTokenizerNoSplit()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array( 'foo', ezcDocumentPdfTokenizer::FORCED ),
            $hyphenator->tokenize( 'foo' )
        );
    }

    public function testDefaultTokenizerSingleMiddleSplit()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array( 'foo', ' ', 'bar', ezcDocumentPdfTokenizer::FORCED ),
            $hyphenator->tokenize( 'foo bar' )
        );
    }

    public function testDefaultTokenizerConvertTab1()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array( '        ', ezcDocumentPdfTokenizer::FORCED ),
            $hyphenator->tokenize( "\t" )
        );
    }

    public function testDefaultTokenizerConvertTab2()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array( '        ', ezcDocumentPdfTokenizer::FORCED ),
            $hyphenator->tokenize( "   \t" )
        );
    }

    public function testDefaultTokenizerConvertTab3()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array( '                ', ezcDocumentPdfTokenizer::FORCED ),
            $hyphenator->tokenize( "          \t" )
        );
    }

    public function testDefaultTokenizerConvertTab4()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array( 'foo', '     ', 'bar', ezcDocumentPdfTokenizer::FORCED ),
            $hyphenator->tokenize( "foo\tbar" )
        );
    }

    public function testDefaultTokenizerConvertTab5()
    {
        $hyphenator = new ezcDocumentPdfLiteralTokenizer();
        $this->assertSame(
            array(
                'foo', '     ', 'bar', ezcDocumentPdfTokenizer::FORCED,
                'foo', '     ', 'bar', ezcDocumentPdfTokenizer::FORCED
            ),
            $hyphenator->tokenize( "foo\tbar\nfoo\tbar" )
        );
    }
}
?>

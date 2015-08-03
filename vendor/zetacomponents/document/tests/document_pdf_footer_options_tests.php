<?php
/**
 * ezcDocTestConvertXhtmlDocbook
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

require_once dirname( __FILE__ ) . '/options_test_case.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfFooterOptionsTests extends ezcDocumentOptionsTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function getOptionsClassName()
    {
        return 'ezcDocumentPdfFooterOptions';
    }

    public static function provideDefaultValues()
    {
        return array(
            array(
                'height', ezcDocumentPcssMeasure::create( '15mm' ),
            ),
            array(
                'footer', true,
            ),
            array(
                'showDocumentTitle', true,
            ),
            array(
                'showDocumentAuthor', true,
            ),
            array(
                'showPageNumber', true,
            ),
            array(
                'pageNumberOffset', 0,
            ),
            array(
                'centerPageNumber', false,
            ),
        );
    }

    public static function provideValidData()
    {
        return array(
            array(
                'footer',
                array( true, false ),
            ),
            array(
                'showDocumentTitle',
                array( true, false ),
            ),
            array(
                'showDocumentAuthor',
                array( true, false ),
            ),
            array(
                'showPageNumber',
                array( true, false ),
            ),
            array(
                'centerPageNumber',
                array( true, false ),
            ),
            array(
                'pageNumberOffset',
                array( 0, 1, 23 ),
            ),
        );
    }

    public static function provideInvalidData()
    {
        return array(
            array(
                'height',
                array( '15nm', 'foo', new StdClass() ),
            ),
            array(
                'footer',
                array( 1, 23, 'foo', new StdClass() ),
            ),
            array(
                'showDocumentTitle',
                array( 1, 23, 'foo', new StdClass() ),
            ),
            array(
                'showDocumentAuthor',
                array( 1, 23, 'foo', new StdClass() ),
            ),
            array(
                'showPageNumber',
                array( 1, 23, 'foo', new StdClass() ),
            ),
            array(
                'centerPageNumber',
                array( 1, 23, 'foo', new StdClass() ),
            ),
            array(
                'pageNumberOffset',
                array( true, 'foo', new StdClass() ),
            ),
        );
    }
}

?>

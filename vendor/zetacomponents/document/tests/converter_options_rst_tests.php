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
class ezcConverterRstOptionsTests extends ezcDocumentOptionsTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function getOptionsClassName()
    {
        return 'ezcDocumentDocbookToRstConverterOptions';
    }

    public static function provideDefaultValues()
    {
        return array(
            array(
                'headerTypes', array( '==', '--', '=', '-', '^', '~', '`', '*', ':', '+', '/', '.', ),
            ),
            array(
                'wordWrap', 78,
            ),
            array(
                'itemListCharacter', '-',
            ),
        );
    }

    public static function provideValidData()
    {
        return array(
            array(
                'headerTypes',
                array( array( '--' ), array( '--', '=', '"' ) ),
            ),
            array(
                'wordWrap',
                array( 20, 1023 ),
            ),
            array(
                'itemListCharacter',
                array( '*', "\xe2\x80\xa2" ),
            ),
        );
    }

    public static function provideInvalidData()
    {
        return array(
            array(
                'headerTypes',
                array( '--', 23 ),
            ),
            array(
                'wordWrap',
                array( 'foo', new StdClass() ),
            ),
            array(
                'itemListCharacter',
                array( '>', 23 ),
            ),
        );
    }
}

?>

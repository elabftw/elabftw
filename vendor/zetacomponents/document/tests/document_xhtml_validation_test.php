<?php
/**
 * ezcDocumentRstParserTests
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
class ezcDocumentXhtmlValidationTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testSuccessfulDocumentStringValidation()
    {
        $xhtml = new ezcDocumentXhtml();

        $this->assertSame(
            true,
            $xhtml->validateString( file_get_contents( dirname( __FILE__ ) . '/files/xhtml/validation/valid_markup.html' ) ),
            'Expected true as result of document validation'
        );
    }

    public function testSuccessfulDocumentFileValidation()
    {
        $xhtml = new ezcDocumentXhtml();

        $this->assertSame(
            true,
            $xhtml->validateFile( dirname( __FILE__ ) . '/files/xhtml/validation/valid_markup.html' ),
            'Expected true as result of document validation'
        );
    }

    public function testDocumentStringValidationErrors()
    {
        $xhtml = new ezcDocumentXhtml();

        $errors = $xhtml->validateString( file_get_contents( dirname( __FILE__ ) . '/files/xhtml/validation/invalid_markup.html' ) );

        $this->assertTrue( 
            is_array( $errors ),
            'Expected an array of errors to be returned'
        );

        $this->assertTrue( 
            $errors[0] instanceof ezcDocumentValidationError,
            'Expected an array of ezcDocumentValidationError objects to be returned'
        );

        $this->assertSame(
            10,
            count( $errors ),
            'Expected three errors to be found in validated document.'
        );

        $this->assertTrue( 
            $errors[0]->getOriginalError() instanceof LibXMLError,
            'Expected an array of LibXMLError objects to be returned'
        );

        $this->assertSame(
            'Fatal error in 38:7: Opening and ending tag mismatch: a line 36 and h1.',
            (string) $errors[0]
        );
    }
}

?>

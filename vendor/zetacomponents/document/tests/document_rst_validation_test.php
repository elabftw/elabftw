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
class ezcDocumentRstValidationTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testSuccessfulDocumentStringValidation()
    {
        $xhtml = new ezcDocumentRst();

        $this->assertSame(
            true,
            $xhtml->validateString( file_get_contents( dirname( __FILE__ ) . '/files/rst/validation/valid.txt' ) ),
            'Expected true as result of document validation'
        );
    }

    public function testSuccessfulDocumentFileValidation()
    {
        $xhtml = new ezcDocumentRst();

        $this->assertSame(
            true,
            $xhtml->validateFile( dirname( __FILE__ ) . '/files/rst/validation/valid.txt' ),
            'Expected true as result of document validation'
        );
    }

    public function testDocumentFatalParseError()
    {
        $xhtml = new ezcDocumentRst();

        $errors = $xhtml->validateFile( dirname( __FILE__ ) . '/files/rst/validation/parser_fatal.txt' );

        $this->assertTrue( 
            is_array( $errors ),
            'Expected an array of errors to be returned'
        );

        $this->assertTrue( 
            $errors[0] instanceof ezcDocumentValidationError,
            'Expected an array of ezcDocumentValidationError objects to be returned'
        );

        $this->assertSame(
            1,
            count( $errors ),
            'Expected three errors to be found in validated document.'
        );

        $this->assertTrue( 
            $errors[0]->getOriginalError() instanceof ezcDocumentParserException,
            'Expected an array of ezcDocumentParserException objects to be returned'
        );

        $this->assertSame(
            'Parse error: Fatal error: \'Unexpected indentation change from level 4 to 0.\' in line 4 at position 38.',
            (string) $errors[0]
        );
    }

    public function testDocumentParseNotices()
    {
        $xhtml = new ezcDocumentRst();

        $errors = $xhtml->validateFile( dirname( __FILE__ ) . '/files/rst/validation/parser_notice.txt' );

        $this->assertTrue( 
            is_array( $errors ),
            'Expected an array of errors to be returned'
        );

        $this->assertSame(
            2,
            count( $errors ),
            'Expected three errors to be found in validated document.'
        );
    }

    public function testDocumentVisitorNotices()
    {
        $xhtml = new ezcDocumentRst();

        $errors = $xhtml->validateFile( dirname( __FILE__ ) . '/files/rst/validation/visitor_warning.txt' );

        $this->assertTrue( 
            $errors[0] instanceof ezcDocumentValidationError,
            'Expected an array of ezcDocumentValidationError objects to be returned'
        );

        $this->assertSame(
            1,
            count( $errors ),
            'Expected three errors to be found in validated document.'
        );

        $this->assertTrue( 
            $errors[0]->getOriginalError() instanceof ezcDocumentVisitException,
            'Expected an array of ezcDocumentVisitException objects to be returned'
        );

        $this->assertSame(
            'Visitor error: Warning: \'Too few anonymous reference targets.\' in line 0 at position 0.',
            (string) $errors[0]
        );
    }
}

?>

<?php
/**
 * ezcDocumentDocbookTests
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
class ezcDocumentDocbookTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getDocbookDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/docbook/rst/s_*.xml' );

            // Create array with the test file and the expected result file
            foreach ( $testFiles as $file )
            {
                self::$testDocuments[] = array(
                    $file
                );
            }
        }

        return self::$testDocuments;
        return array_slice( self::$testDocuments, 18, 40 );
    }

    /**
     * @dataProvider getDocbookDocuments
     */
    public function testValidateDocbook( $file )
    {
        $doc = new ezcDocumentDocbook();
        $this->assertTrue(
            $doc->validateFile( $file )
        );
    }

    public function testValidateErrorneousDocbook()
    {
        $doc = new ezcDocumentDocbook();
        $this->assertTrue(
            is_array( $errors = $doc->validateFile( dirname( __FILE__ ) . '/files/docbook/errorneous.xml' ) )
        );

        $this->assertSame(
            'Fatal error in 7:13: Opening and ending tag mismatch: section line 4 and Section.',
            (string) $errors[0]
        );
    }

    public function testValidateInvalidDocbook()
    {
        $doc = new ezcDocumentDocbook();
        $this->assertTrue(
            is_array( $errors = $doc->validateFile( dirname( __FILE__ ) . '/files/docbook/invalid.xml' ) )
        );

        $this->assertSame(
            'Error in 4:0: Element \'{http://docbook.org/ns/docbook}section\', attribute \'id\': The attribute \'id\' is not allowed..',
            (string) $errors[0]
        );
    }
}

?>

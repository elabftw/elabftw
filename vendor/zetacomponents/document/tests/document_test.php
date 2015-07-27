<?php
/**
 * ezcDocumentConverterEzp3TpEzp4Tests
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
class ezcDocumentDocumentTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testConstructWithOptions()
    {
        $options = new ezcDocumentDocbookOptions();
        $options->errorReporting = E_PARSE;

        $document = new ezcDocumentDocbook( $options );

        $this->assertSame(
            E_PARSE,
            $document->options->errorReporting
        );
    }

    public function testNoSuchPropertyException()
    {
        $document = new ezcDocumentDocbook();

        try
        {
            $document->notExistingOption;
            $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        { /* Expected */ }
    }

    public function testSetOptionsProperty()
    {
        $document = new ezcDocumentDocbook();
        $options = new ezcDocumentDocbookOptions();
        $options->errorReporting = E_PARSE;
        $document->options = $options;

        $this->assertSame(
            E_PARSE,
            $document->options->errorReporting
        );

        try
        {
            $document->options = false;
            $this->fail( 'Expected ezcBaseValueException.' );
        }
        catch ( ezcBaseValueException $e )
        { /* Expected */ }
    }

    public function testSetNotExistingProperty()
    {
        $document = new ezcDocumentDocbook();

        try
        {
            $document->notExistingOption = false;
            $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        { /* Expected */ }
    }

    public function testPropertyIsset()
    {
        $document = new ezcDocumentDocbook();

        $this->assertTrue( isset( $document->options ) );
        $this->assertFalse( isset( $document->notExistingOption ) );
    }
}

?>

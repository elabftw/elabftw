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
class ezcDocumentConverterTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testConstructWithOptions()
    {
        $options = new ezcDocumentDocbookToHtmlConverterOptions();
        $options->formatOutput = true;

        $converter = new ezcDocumentDocbookToHtmlConverter( $options );

        $this->assertSame(
            true,
            $converter->options->formatOutput
        );
    }

    public function testNoSuchPropertyException()
    {
        $converter = new ezcDocumentDocbookToHtmlConverter();

        try
        {
            $converter->notExistingOption;
            $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        { /* Expected */ }
    }

    public function testSetOptionsProperty()
    {
        $converter = new ezcDocumentDocbookToHtmlConverter();
        $options = new ezcDocumentDocbookToHtmlConverterOptions();
        $options->formatOutput = true;
        $converter->options = $options;

        $this->assertSame(
            true,
            $converter->options->formatOutput
        );

        try
        {
            $converter->options = 42;
            $this->fail( 'Expected ezcBaseValueException.' );
        }
        catch ( ezcBaseValueException $e )
        { /* Expected */ }
    }

    public function testSetNotExistingProperty()
    {
        $converter = new ezcDocumentDocbookToHtmlConverter();

        try
        {
            $converter->notExistingOption = 42;
            $this->fail( 'Expected ezcBasePropertyNotFoundException.' );
        }
        catch ( ezcBasePropertyNotFoundException $e )
        { /* Expected */ }
    }

    public function testPropertyIsset()
    {
        $converter = new ezcDocumentDocbookToHtmlConverter();

        $this->assertTrue( isset( $converter->options ) );
        $this->assertFalse( isset( $converter->notExistingOption ) );
    }
}

?>

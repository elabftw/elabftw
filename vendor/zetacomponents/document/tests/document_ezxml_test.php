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

require_once 'helper/rst_dummy_directives.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentEzXmlTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testValidateValidDocument()
    {
        $doc = new ezcDocumentEzXml();

        $this->assertSame(
            $doc->validateFile( dirname( __FILE__ ) . '/files/ezxml/s_001_header.ezp' ),
            true
        );
    }

    public function testValidateInvalidDocument()
    {
        $invalid = dirname( __FILE__ ) . '/files/ezxml/e_000_invalid.ezp';
        $doc = new ezcDocumentEzXml();

        $this->assertEquals(
            count( $errors = $doc->validateFile( $invalid ) ),
            count( $doc->validateString( file_get_contents( $invalid ) ) )
        );

        $this->assertEquals(
            (string) $errors[0],
            'Error in 6:0: Did not expect element unknown there.'
        );
    }

    public function testUnhandledLinkType()
    {
        $invalid = dirname( __FILE__ ) . '/files/ezxml/e_001_unhandled_link.ezp';
        $document = new ezcDocumentEzXml();
        $document->loadFile( $invalid );

        try
        {
            $docbook = $document->getAsDocbook();
            $this->fail( 'Expected ezcDocumentConversionException.' );
        }
        catch ( ezcDocumentConversionException $e )
        {
            $this->assertSame(
                $e->getMessage(),
                'Conversion error: Warning: \'Unhandled link type.\'.'
            );
        }
    }

    public function testCreateFromDocbook()
    {
        $from = dirname( __FILE__ ) . '/files/docbook/ezxml/s_001_empty.xml';
        $to   = dirname( __FILE__ ) . '/files/docbook/ezxml/s_001_empty.ezp';

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( $from );

        $document = new ezcDocumentEzXml();
        $document->createFromDocbook( $docbook );

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'docbook_ezxml_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), $xml = $document->save() );

        $this->assertEquals(
            file_get_contents( $to ),
            $xml,
            'Document not visited as expected.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }

    public static function getEzXmlTestDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/ezxml/s_*.ezp' );

            // Create array with the test file and the expected result file
            foreach ( $testFiles as $file )
            {
                self::$testDocuments[] = array(
                    $file,
                    substr( $file, 0, -3 ) . 'xml'
                );
            }
        }

        return self::$testDocuments;
        return array_slice( self::$testDocuments, 6, 1 );
    }

    /**
     * @dataProvider getEzXmlTestDocuments
     */
    public function testConvertToDocbook( $from, $to )
    {
        if ( !is_file( $to ) )
        {
            $this->markTestSkipped( "Comparision file '$to' not yet defined." );
        }

        $document = new ezcDocumentEzXml();
        $document->loadFile( $from );

        $docbook = $document->getAsDocbook();
        $xml = $docbook->save();

        $this->assertTrue(
            $docbook instanceof ezcDocumentDocbook
        );

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'ezxml_docbook_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), $xml );

        // We need a proper XSD first, the current one does not accept legal
        // XML.
//        $this->checkDocbook( $docbook->getDomDocument() );

        $this->assertEquals(
            file_get_contents( $to ),
            $xml,
            'Document not visited as expected.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }
}

?>

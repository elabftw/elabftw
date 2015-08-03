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

require_once 'helper/docbook_rst_address_element.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentConverterDocbookToRstTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testCreateDocumentFromDocbook()
    {
        $doc = new ezcDocumentDocbook();
        $doc->loadFile( dirname( __FILE__ ) . '/files/docbook/rst/s_001_empty.xml' );

        $rst = new ezcDocumentRst();
        $rst->createFromDocbook( $doc );

        $this->assertSame(
            $rst->save(),
            file_get_contents( dirname( __FILE__ ) . '/files/docbook/rst/s_001_empty.txt' )
        );
    }

    public function testCreateDocumentFromErrneousDocbook()
    {
        $doc = new ezcDocumentDocbook();
        $doc->options->failOnError = false;
        $doc->loadFile( dirname( __FILE__ ) . '/files/docbook/errorneous.xml' );

        $rst = new ezcDocumentRst();
        try 
        {
            $rst->createFromDocbook( $doc );
            $this->fail( 'Expected ezcDocumentVisitException.' );
        } catch ( ezcDocumentVisitException $e )
        { /* Expected */ }
    }

    public function testCustomeElementHandler()
    {
        $doc = new ezcDocumentDocbook();
        $doc->loadFile( dirname( __FILE__ ) . '/files/docbook/rst/h_001_address.xml' );

        $converter = new ezcDocumentDocbookToRstConverter();
        $converter->setElementHandler( 'docbook', 'address', new myAddressElementHandler() );

        $rst = $converter->convert( $doc );

        $this->assertSame(
            $rst->save(),
            file_get_contents( dirname( __FILE__ ) . '/files/docbook/rst/h_001_address.txt' )
        );
    }

    public static function getTestDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/docbook/rst/s_*.xml' );

            // Create array with the test file and the expected result file
            foreach ( $testFiles as $file )
            {
                self::$testDocuments[] = array(
                    $file,
                    substr( $file, 0, -3 ) . 'txt'
                );
            }
        }

        return self::$testDocuments;
        return array_slice( self::$testDocuments, 0, 3 );
    }

    /**
     * @dataProvider getTestDocuments
     */
    public function testLoadXmlDocumentFromFile( $from, $to )
    {
        if ( !is_file( $to ) )
        {
            $this->markTestSkipped( "Comparision file '$to' not yet defined." );
        }

        $doc = new ezcDocumentDocbook();
        $doc->loadFile( $from );

        $converter = new ezcDocumentDocbookToRstConverter();
        $created = $converter->convert( $doc );

        $this->assertTrue(
            $created instanceof ezcDocumentRst
        );

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'docbook_rst_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), $text = $created->save() );

        $this->assertTrue(
            ( $errors = $created->validateString( $text ) ) === true,
            ( is_array( $errors ) ? implode( PHP_EOL, $errors ) : 'Expected true' )
        );

        $this->assertEquals(
            file_get_contents( $to ),
            $text
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }
}

?>

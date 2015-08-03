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
require_once 'helper/rst_paragraph_directive.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentRstDocbookVisitorTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getTestDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/rst/docbook/s_*.txt' );

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
        return array_slice( self::$testDocuments, -2, 1 );
    }

    /**
     * @dataProvider getTestDocuments
     */
    public function testParseRstFile( $from, $to )
    {
        if ( !is_file( $to ) )
        {
            $this->markTestSkipped( "Comparision file '$to' not yet defined." );
        }

        $document = new ezcDocumentRst();
        $document->options->errorReporting = E_PARSE | E_ERROR | E_WARNING;

        $document->registerDirective( 'my_custom_directive', 'ezcDocumentTestDummyDirective' );
        $document->registerDirective( 'user', 'ezcDocumentTestDummyDirective' );
        $document->registerDirective( 'book', 'ezcDocumentTestDummyDirective' );
        $document->registerDirective( 'function', 'ezcDocumentTestDummyDirective' );
        $document->registerDirective( 'replace', 'ezcDocumentTestDummyDirective' );
        $document->registerDirective( 'paragraph', 'ezcDocumentTestParagraphDirective' );
        $document->registerRole( 'my_role', 'ezcDocumentTestDummyRole' );

        $document->loadFile( $from );

        $docbook = $document->getAsDocbook();
        $xml = $docbook->save();

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'docbook_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), $xml );

        // Validate generated docbook
        $this->assertTrue( $docbook->validateString( $xml ) );

        $this->assertEquals(
            file_get_contents( $to ),
            $xml,
            'Document not visited as expected.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }

    public static function getErroneousTestDocuments()
    {
//        return array();
        return array(
            array(
                dirname( __FILE__ ) . '/files/rst/docbook/e_001_missing_directive.txt',
                'Visitor error: Warning: \'No directive handler registered for directive \'missing_directive_dclaration\'.\' in line 7 at position 1.',
            ),
            array(
                dirname( __FILE__ ) . '/files/rst/docbook/e_001_missing_role.txt',
                'Visitor error: Warning: \'No text role handler registered for text role \'no-handler-registered\'.\' in line 4 at position 45.',
            ),
        );
    }

    /**
     * @dataProvider getErroneousTestDocuments
     */
    public function testParseErroneousRstFile( $file, $message )
    {
        try
        {
            $document = new ezcDocumentRst();
            $document->options->errorReporting = E_PARSE | E_ERROR | E_WARNING;

            $document->registerDirective( 'my_custom_directive', 'ezcDocumentTestDummyDirective' );
            $document->registerDirective( 'user', 'ezcDocumentTestDummyDirective' );
            $document->registerDirective( 'book', 'ezcDocumentTestDummyDirective' );
            $document->registerDirective( 'function', 'ezcDocumentTestDummyDirective' );
            $document->registerDirective( 'replace', 'ezcDocumentTestDummyDirective' );

            $document->loadFile( $file );

            $docbook = $document->getAsDocbook();
            $xml = $docbook->save();
            $document = $parser->parse( $tokenizer->tokenizeFile( $file ) );
            $this->fail( 'Expected some exception.' );
        }
        catch ( ezcDocumentException $e )
        {
            $this->assertSame(
                $message,
                $e->getMessage(),
                'Different parse error expected.'
            );
        }
    }
}

?>

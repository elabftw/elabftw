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
class ezcDocumentOdtDocbookTests extends ezcTestCase
{
    public static $testDocuments;

    protected $cwd;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        // The pathes in the processed files are relativ to one directory level
        // above, so we just change the curretn working dir.
        $this->cwd = getcwd();
        chdir( dirname( $this->cwd ) );
    }

    public function tearDown()
    {
        chdir( $this->cwd );
    }

    public static function getTestDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/odt/tests/s_*.fodt' );

            // Create array with the test file and the expected result file
            foreach ( $testFiles as $file )
            {
                self::$testDocuments[] = array(
                    $file,
                    substr( $file, 0, -4 ) . 'xml'
                );
            }
        }

        return self::$testDocuments;
    }

    public function testBadMarkup()
    {
        $document = new ezcDocumentOdt();

        try
        {
            $document->loadString(
                file_get_contents(
                    dirname( __FILE__ ) . '/files/odt/bad_markup/broken_xml.fodt'
                )
            );
            $this->fail( 'Exception not thrown on load of invalid markup.' );
        }
        catch ( ezcDocumentErroneousXmlException $e )
        {
            $this->assertEquals(
                'Errors occured while parsing the XML.',
                $e->getMessage()
            );
        }
    }

    public function testInvalidDocBook()
    {

        $docbook = new ezcDocumentDocbook();
        $docbook->options->validate = false;
        $docbook->loadFile( dirname( __FILE__ ) . '/files/docbook/invalid.xml' );

        $document = new ezcDocumentOdt();
        $document->options->validate = true;

        try
        {
            $document->createFromDocbook( $docbook );
            $this->fail( 'Exception not thrown on conversion of invalid docbook.' );
        }
        catch ( ezcDocumentVisitException $e ) {}
    }

    public function testValidateFileSuccess()
    {
        $document = new ezcDocumentOdt();

        $actRes = $document->validateFile(
            dirname( __FILE__ ) . '/files/odt/tests/s_000_simple.fodt'
        );

        $this->assertTrue( $actRes );
    }

    public function testValidateFileFailure()
    {
        $document = new ezcDocumentOdt();

        $actRes = $document->validateFile(
            dirname( __FILE__ ) . '/files/odt/invalid/s_000_simple.fodt'
        );

        $this->assertInternalType(
            'array',
            $actRes
        );
        $this->assertEquals(
            1,
            count( $actRes )
        );
    }

    /**
     * @dataProvider getTestDocuments
     */
    public function testCreateFromDocbook( $to, $from )
    {
        // Tested for correctness in converter tests!

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( $from );

        $document = new ezcDocumentOdt();
        $document->createFromDocbook( $docbook );
        
        $this->assertNotNull(
            $document->getDomDocument()
        );
    }

    /**
     * @dataProvider getTestDocuments
     */
    public function testCommonConversions( $from, $to )
    {

        $tempDir = $this->createTempDir( 'odt_tests_' ) . '/';
        $imgDir = $tempDir . 'img';

        mkdir( $imgDir );

        $options = new ezcDocumentOdtOptions();
        $options->imageDir = $imgDir;

        $document = new ezcDocumentOdt();
        $document->setFilters(
            array(
                new ezcDocumentOdtImageFilter( $options ),
                new ezcDocumentOdtElementFilter(),
                new ezcDocumentOdtStyleFilter(),
            )
        );
        $document->loadFile( $from );

        $docbook = $document->getAsDocbook();
        $xml = $docbook->save();

        $xml = $this->verifyAndReplaceImages( basename( $to, '.xml' ), $xml );

        // Store test file, to have something to compare on failure
        file_put_contents( $tempDir . basename( $to ), $xml );

        $this->assertTrue( $docbook->validateString( $xml ) );

        if ( !is_file( $to ) )
        {
            $this->fail( "Missing comparison file '$to'." );
        }

        $this->assertEquals(
            file_get_contents( $to ),
            $xml,
            'Document not visited as expected.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }


    /**
     * Verify extracted images from an FODT and replace their links for 
     * comparison.
     * 
     * @param string $testDir Name of the current test sub-dir
     * @param string $xml 
     * @return string XML with image refs replaced
     */
    protected function verifyAndReplaceImages( $testDir, $xml )
    {
        $dom = new DOMDocument();
        $dom->loadXml( $xml );

        $xpath = new DOMXPath( $dom );
        $xpath->registerNamespace( 'doc', 'http://docbook.org/ns/docbook' );

        $images = $xpath->query( '//doc:imagedata' );

        $i = 1;
        foreach ( $images as $image )
        {
            $refFile = "Document/tests/files/odt/tests/$testDir/$i.png";
            if ( !file_exists( $refFile ) )
            {
                $this->fail( "Image reference with '$refFile' does not exist." );
            }

            $imageFile = $image->getAttribute( 'fileref' );

            $this->assertFileEquals(
                $refFile,
                $imageFile,
                "Extracted image $i did not match ref file '$refFile'."
            );
            
            $image->setAttribute( 'fileref', $refFile );

            ++$i;
        }

        return $dom->saveXml();
    }
}

?>

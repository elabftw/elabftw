<?php
/**
 * ezcDocumentPdfDriverTcpdfTests
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

require_once 'base.php';

// Try to include TCPDF class from external/tcpdf.
// @TODO: Maybe also search the include path...
if ( file_exists( $path = dirname( __FILE__ ) . '/../external/tcpdf-4.8/tcpdf.php' ) )
{
    include $path;
}

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfRendererTextDecorationsTests extends ezcDocumentPdfTestCase
{
    protected $document;
    protected $xpath;
    protected $styles;
    protected $page;

    /**
     * Old error reporting level restored after the test
     * 
     * @var int
     */
    protected $oldErrorReporting = -1;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        parent::setUp();

        $this->document = new DOMDocument();
        $this->document->registerNodeClass( 'DOMElement', 'ezcDocumentLocateableDomElement' );

        $this->document->load( dirname( __FILE__ ) . '/../files/pdf/paragraph.xml' );

        $this->xpath = new DOMXPath( $this->document );
        $this->xpath->registerNamespace( 'doc', 'http://docbook.org/ns/docbook' );

        $this->styles = new ezcDocumentPcssStyleInferencer();
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'article' ),
                array(
                    'font-size' => '8mm',
                )
            ),
            new ezcDocumentPcssLayoutDirective(
                array( 'para' ),
                array(
                    'margin' => '0mm',
                )
            ),
        ) );

        $this->page = new ezcDocumentPdfPage( 1, 108, 108, 100, 100 );
        $this->page->x = 0;
        $this->page->y = 0;
    }

    public function tearDown()
    {
        error_reporting( $this->oldErrorReporting );
        parent::tearDown();
    }

    /**
     * Return an array of drivers to test with.
     * 
     * @return void
     */
    public static function getDrivers()
    {
        return array(
            array( new ezcDocumentPdfSvgDriver() ),
            array( new ezcDocumentPdfHaruDriver() ),
            array( new ezcDocumentPdfTcpdfDriver() ),
        );
    }

    /**
     * Ensure the test environment is properly set up for the currently
     * selected driver.
     */
    protected function checkTestEnv( ezcDocumentPdfDriver $driver )
    {
        switch ( true )
        {
            case $driver instanceof ezcDocumentPdfSvgDriver:
                $this->extension = 'svg';
                break;

            case $driver instanceof ezcDocumentPdfHaruDriver:
                if ( !ezcBaseFeatures::hasExtensionSupport( 'haru' ) )
                {
                    $this->markTestSkipped( 'This test requires pecl/haru installed.' );
                }
                break;

            case $driver instanceof ezcDocumentPdfTcpdfDriver:
                if ( !class_exists( 'TCPDF' ) )
                {
                    $this->markTestSkipped( 'This test requires the TCPDF class.' );
                }

                // Change error reporting - this is evil, but otherwise TCPDF will
                // abort the tests, because it throws lots of E_NOTICE and
                // E_DEPRECATED.
                $this->oldErrorReporting = error_reporting( E_PARSE | E_ERROR | E_WARNING );
                break;
        }
    }

    protected function renderPdf( ezcDocumentPdfDriver $driver, $paragraph = 2 )
    {
        $this->checkTestEnv( $driver );

        $transactionalDriver = new ezcDocumentPdfTransactionalDriverWrapper();
        $transactionalDriver->setDriver( $driver );

        $driver->createPage( 108, 108 );
        $renderer  = new ezcDocumentPdfWrappingTextBoxRenderer( $transactionalDriver, $this->styles );
        $renderer->renderNode(
            $this->page,
            new ezcDocumentPdfDefaultHyphenator(),
            new ezcDocumentPdfDefaultTokenizer(),
            $this->xpath->query( '//doc:para' )->item( $paragraph ),
            new ezcDocumentPdfMainRenderer( $transactionalDriver, $this->styles )
        );
        $transactionalDriver->commit();

        return $driver->save();
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphWithoutMarkup( ezcDocumentPdfDriver $driver )
    {
        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphColoredEmphasis( ezcDocumentPdfDriver $driver )
    {
        // Additional formatting
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'emphasis' ),
                array(
                    'color' => '#ce5c00',
                )
            )
        ) );

        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphBackgroundColor( ezcDocumentPdfDriver $driver )
    {
        // Additional formatting
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'emphasis' ),
                array(
                    'background-color' => '#d3d7cf',
                )
            )
        ) );

        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphLineThrough( ezcDocumentPdfDriver $driver )
    {
        // Additional formatting
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'emphasis' ),
                array(
                    'text-decoration' => 'line-through',
                )
            )
        ) );

        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphOverline( ezcDocumentPdfDriver $driver )
    {
        // Additional formatting
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'emphasis' ),
                array(
                    'text-decoration' => 'overline',
                )
            )
        ) );

        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphUnderline( ezcDocumentPdfDriver $driver )
    {
        // Additional formatting
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'emphasis' ),
                array(
                    'text-decoration' => 'underline',
                )
            )
        ) );

        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderParagraphAllDecorations( ezcDocumentPdfDriver $driver )
    {
        // Additional formatting
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'emphasis' ),
                array(
                    'background-color' => '#d3d7cf',
                    'text-decoration'  => 'overline underline line-through',
                )
            )
        ) );

        $pdf = $this->renderPdf( $driver );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }

    /**
     * @dataProvider getDrivers
     */
    public function testRenderExternalLinks( ezcDocumentPdfDriver $driver )
    {
        if ( $driver instanceof ezcDocumentPdfSvgDriver )
        {
            $this->markTestSkipped( 'Not supported by the SVG driver.' );
        }

        $pdf = $this->renderPdf( $driver, 5 );
        $this->assertPdfDocumentsSimilar( $pdf, get_class( $driver ) . '_' . __FUNCTION__ );
    }
}

?>

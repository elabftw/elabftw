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

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfMainRendererTests extends ezcDocumentPdfTestCase
{
    protected $document;
    protected $xpath;
    protected $styles;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testRenderUnknownElements()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/unknown.xml' );

        try {
            $renderer  = new ezcDocumentPdfMainRenderer(
                new ezcDocumentPdfSvgDriver(),
                new ezcDocumentPcssStyleInferencer()
            );

            $pdf = $renderer->render(
                $docbook,
                new ezcDocumentPdfDefaultHyphenator()
            );
            $this->fail( 'Expected ezcDocumentVisitException.' );
        }
        catch ( ezcDocumentVisitException $e )
        { /* Expected */ }
    }

    public function testRenderUnknownElementsSilence()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/unknown.xml' );

        $options  = new ezcDocumentPdfOptions();
        $options->errorReporting = E_PARSE;
        $renderer = new ezcDocumentPdfMainRenderer(
            new ezcDocumentPdfSvgDriver(),
            new ezcDocumentPcssStyleInferencer(),
            $options
        );

        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        $errors = $renderer->getErrors();
        $this->assertEquals( 1, count( $errors ) );
        $this->assertEquals(
            'Visitor error: Notice: \'Unknown and unhandled element: http://example.org/unknown:article.\' in line 0 at position 0.',
            reset( $errors )->getMessage()
        );
    }

    public function testRenderMainSinglePage()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/long_text.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array()
        );
    }

    public function testRenderMainSinglePageNotNamespaced()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/paragraph_nons.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array()
        );
    }

    public function testRenderMainMulticolumnLayout()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/long_text.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array(
                new ezcDocumentPcssLayoutDirective(
                    array( 'article' ),
                    array(
                        'text-columns' => '3',
                        'line-height'  => '1',
                    )
                ),
            )
        );
    }

    public function testRenderLongTextParagraphConflict()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/test_long_wrapping.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array()
        );
    }

    public function testRenderLongTextWithInternalLinks()
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'haru' ) )
        {
            $this->markTestSkipped( 'This test requires pecl/haru installed.' );
        }

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/internal_links.xml' );

        $style = new ezcDocumentPcssStyleInferencer();
        $style->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'page' ),
                array(
                    'page-size' => 'A6',
                )
            ),
        ) );

        $renderer  = new ezcDocumentPdfMainRenderer(
            new ezcDocumentPdfHaruDriver(),
            $style
        );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        $this->assertPdfDocumentsSimilar( $pdf, __CLASS__ . '_' . __FUNCTION__ );
    }

    public function testRenderUnavailableCustomFont()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/wrapping.xml' );

        $style = new ezcDocumentPcssStyleInferencer();
        $style->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'article' ),
                array(
                    'font-family' => 'my-font',
                )
            ),
        ) );

        $renderer  = new ezcDocumentPdfMainRenderer(
            new ezcDocumentPdfSvgDriver(),
            $style
        );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        $this->assertPdfDocumentsSimilar( $pdf, __CLASS__ . '_' . __FUNCTION__ );
    }

    public function testRenderCustomFont()
    {
        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/wrapping.xml' );

        $style = new ezcDocumentPcssStyleInferencer();
        $style->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'article' ),
                array(
                    'font-family' => 'my-font',
                )
            ),
            new ezcDocumentPcssDeclarationDirective(
                '@font-face',
                array(
                    'font-family' => 'my-font',
                    'src'         => 'url( ' . dirname( __FILE__ ) . '/../files/fonts/font.ttf )',
                )
            ),
        ) );

        $renderer  = new ezcDocumentPdfMainRenderer(
            new ezcDocumentPdfSvgDriver(),
            $style
        );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        $this->assertPdfDocumentsSimilar( $pdf, __CLASS__ . '_' . __FUNCTION__ );
    }
}

?>

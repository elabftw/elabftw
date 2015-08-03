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
class ezcDocumentPdfRendererFooterPartTests extends ezcDocumentPdfTestCase
{
    protected $renderer;
    protected $docbook;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        parent::setUp();

        $style = new ezcDocumentPcssStyleInferencer();
        $style->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'article' ),
                array(
                    'font-family'  => 'serif',
                    'text-columns' => '2',
                    'font-size'    => '10pt',
                    'line-height'  => '1',
                )
            ),
            new ezcDocumentPcssLayoutDirective(
                array( 'title' ),
                array(
                    'font-family'  => 'sans-serif',
                    'text-columns' => '2',
                )
            ),
            new ezcDocumentPcssLayoutDirective(
                array( 'page' ),
                array(
                    'page-size'    => 'A5',
                )
            ),
        ) );

        $this->docbook = new ezcDocumentDocbook();
        $this->docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/long_text.xml' );

        $this->renderer = new ezcDocumentPdfMainRenderer(
            new ezcDocumentPdfSvgDriver(),
            $style
        );
    }

    public function testRenderDefaultFooter()
    {
        $this->renderer->registerPdfPart(
            new ezcDocumentPdfFooterPdfPart()
        );

        $pdf = $this->renderer->render(
            $this->docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        file_put_contents(
            $this->tempDir . ( $fileName = __CLASS__ . '_' . __FUNCTION__ . '.svg' ),
            $pdf
        );
    
        $this->assertXmlFileEqualsXmlFile(
            $this->basePath . 'renderer/' . $fileName,
            $this->tempDir . $fileName
        );
    }

    public function testRenderHeader()
    {
        $this->renderer->registerPdfPart(
            new ezcDocumentPdfFooterPdfPart( new ezcDocumentPdfFooterOptions( array(
                'footer' => false,
            ) ) )
        );

        $pdf = $this->renderer->render(
            $this->docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        file_put_contents(
            $this->tempDir . ( $fileName = __CLASS__ . '_' . __FUNCTION__ . '.svg' ),
            $pdf
        );
    
        $this->assertXmlFileEqualsXmlFile(
            $this->basePath . 'renderer/' . $fileName,
            $this->tempDir . $fileName
        );
    }

    public function testRenderHeaderAndFooter()
    {
        $this->renderer->registerPdfPart(
            new ezcDocumentPdfFooterPdfPart( new ezcDocumentPdfFooterOptions( array(
                'showDocumentTitle'  => false,
                'showDocumentAuthor' => false,
                'pageNumberOffset'   => 7,
                'height'             => '10mm',
            ) ) )
        );

        $this->renderer->registerPdfPart(
            new ezcDocumentPdfHeaderPdfPart( new ezcDocumentPdfFooterOptions( array(
                'showPageNumber'   => false,
                'height'           => '10mm',
            ) ) )
        );

        $pdf = $this->renderer->render(
            $this->docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );

        file_put_contents(
            $this->tempDir . ( $fileName = __CLASS__ . '_' . __FUNCTION__ . '.svg' ),
            $pdf
        );
    
        $this->assertXmlFileEqualsXmlFile(
            $this->basePath . 'renderer/' . $fileName,
            $this->tempDir . $fileName
        );
    }
}

?>

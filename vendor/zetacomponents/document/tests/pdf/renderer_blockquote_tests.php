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

require_once 'renderer_text_box_base_tests.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfBlockquoteRendererTests extends ezcDocumentPdfTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        parent::setUp();

        $this->styles = new ezcDocumentPcssStyleInferencer();
        $this->styles->appendStyleDirectives( array(
            new ezcDocumentPcssLayoutDirective(
                array( 'page' ),
                array(
                    'page-size' => 'TEST',
                    'margin'    => '0',
                    'padding'   => '10',
                )
            ),
            new ezcDocumentPcssLayoutDirective(
                array( 'blockquote' ),
                array(
                    'font-size' => '6mm',
                )
            ),
        ) );
    }

    public function testRenderBlockquote()
    {
        // Additional formatting

        $mock = $this->getMock( 'ezcTestDocumentPdfMockDriver', array(
            'createPage',
            'drawWord',
        ) );

        // Expectations
        $mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 100, 1. ), $this->equalTo( 100, 1. )
        );
        $mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 21, 1. ), $this->equalTo( "Some" )
        );
        $mock->expects( $this->at( 2 ) )->method( 'drawWord' )->with(
            $this->equalTo( 35, 1. ), $this->equalTo( 21, 1. ), $this->equalTo( "random" )
        );
        $mock->expects( $this->at( 4 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 29.4, 1. ), $this->equalTo( "which" )
        );
        $mock->expects( $this->at( 13 ) )->method( 'drawWord' )->with(
            $this->equalTo( 25, 1. ), $this->equalTo( 46.8, 1. ), $this->equalTo( "Name" )
        );

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/blockquote.xml' );

        $renderer  = new ezcDocumentPdfMainRenderer( $mock, $this->styles );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );
    }
}

?>

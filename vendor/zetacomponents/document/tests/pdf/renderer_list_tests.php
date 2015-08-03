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
class ezcDocumentPdfListRendererTests extends ezcDocumentPdfTestCase
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
                array( 'article' ),
                array(
                    'font-size' => '8mm',
                )
            ),
            new ezcDocumentPcssLayoutDirective(
                array( 'page' ),
                array(
                    'page-size' => 'TEST',
                    'margin'    => '0',
                    'padding'   => '10',
                )
            ),
        ) );
    }

    public function testRenderItemizedList()
    {
        $mock = $this->getMock( 'ezcTestDocumentPdfMockDriver', array(
            'createPage',
            'drawWord',
        ) );

        // Expectations
        $mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 100, 1. ), $this->equalTo( 100, 1. )
        );
        $mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "-" )
        );
        $mock->expects( $this->at( 2 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "TrueType" )
        );
        $mock->expects( $this->at( 3 ) )->method( 'drawWord' )->with(
            $this->equalTo( 56, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "fonts." )
        );
        $mock->expects( $this->at( 4 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( "-" )
        );
        $mock->expects( $this->at( 5 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( "PostScript" )
        );
        $mock->expects( $this->at( 6 ) )->method( 'drawWord' )->with(
            $this->equalTo( 64, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( "fonts." )
        );

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/bullet_list.xml' );

        $renderer  = new ezcDocumentPdfMainRenderer( $mock, $this->styles );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );
    }

    public function testRenderOrderedList()
    {
        $mock = $this->getMock( 'ezcTestDocumentPdfMockDriver', array(
            'createPage',
            'drawWord',
        ) );

        // Expectations
        $mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 100, 1. ), $this->equalTo( 100, 1. )
        );
        $mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "1" )
        );
        $mock->expects( $this->at( 2 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "TrueType" )
        );
        $mock->expects( $this->at( 3 ) )->method( 'drawWord' )->with(
            $this->equalTo( 56, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "fonts." )
        );
        $mock->expects( $this->at( 4 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( "2" )
        );
        $mock->expects( $this->at( 5 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( "PostScript" )
        );
        $mock->expects( $this->at( 6 ) )->method( 'drawWord' )->with(
            $this->equalTo( 64, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( "fonts." )
        );

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/ordered_list.xml' );

        $renderer  = new ezcDocumentPdfMainRenderer( $mock, $this->styles );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );
    }

    public function testRenderStackedLists()
    {
        $mock = $this->getMock( 'ezcTestDocumentPdfMockDriver', array(
            'createPage',
            'drawWord',
        ) );

        // Expectations
        $mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 100, 1. ), $this->equalTo( 100, 1. )
        );
        $mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "1" )
        );
        $mock->expects( $this->at( 2 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( "Paragraph." )
        );
        $mock->expects( $this->at( 3 ) )->method( 'drawWord' )->with(
            $this->equalTo( 30, 1. ), $this->equalTo( 30, 1. ), $this->equalTo( "-" )
        );
        $mock->expects( $this->at( 4 ) )->method( 'drawWord' )->with(
            $this->equalTo( 40, 1. ), $this->equalTo( 30, 1. ), $this->equalTo( "List" )
        );
        $mock->expects( $this->at( 7 ) )->method( 'drawWord' )->with(
            $this->equalTo( 30, 1. ), $this->equalTo( 45, 1. ), $this->equalTo( "-" )
        );
        $mock->expects( $this->at( 8 ) )->method( 'drawWord' )->with(
            $this->equalTo( 40, 1. ), $this->equalTo( 45, 1. ), $this->equalTo( "List" )
        );
        $mock->expects( $this->at( 11 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 66, 1. ), $this->equalTo( "2" )
        );
        $mock->expects( $this->at( 12 ) )->method( 'drawWord' )->with(
            $this->equalTo( 20, 1. ), $this->equalTo( 66, 1. ), $this->equalTo( "PostScript" )
        );

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/stacked_list.xml' );

        $renderer  = new ezcDocumentPdfMainRenderer( $mock, $this->styles );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );
    }

    public static function getOrderedListTypes()
    {
        return array(
            array(
                'arabic',
                array( '1', '2' ),
            ),
            array(
                'loweralpha',
                array( 'a', 'b' ),
            ),
            array(
                'lowerroman',
                array( 'i', 'ii' ),
            ),
            array(
                'upperalpha',
                array( 'A', 'B' ),
            ),
            array(
                'upperroman',
                array( 'I', 'II' ),
            ),
            array(
                'unknown',
                array( '1', '2' ),
            ),
        );
    }

    /**
     * @dataProvider getOrderedListTypes
     */
    public function testRenderOrderedListTypes( $type, array $items )
    {
        $mock = $this->getMock( 'ezcTestDocumentPdfMockDriver', array(
            'createPage',
            'drawWord',
        ) );

        // Expectations
        $mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( $items[0] )
        );
        $mock->expects( $this->at( 4 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( $items[1] )
        );

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/ordered_list.xml' );

        // Set numeration type in document
        $dom   = $docbook->getDomDocument();
        $xpath = new DOMXPath( $dom );
        $xpath->registerNamespace( 'doc', 'http://docbook.org/ns/docbook' );
        $list  = $xpath->evaluate( '//doc:orderedlist' )->item( 0 );
        $list->setAttribute( 'numeration', $type );
        $docbook->setDomDocument( $dom );

        $renderer  = new ezcDocumentPdfMainRenderer( $mock, $this->styles );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );
    }

    public static function getItemizedListTypes()
    {
        return array(
            array(
                '*',
                array( '*', '*' ),
            ),
            array(
                '✦',
                array( '✦', '✦' ),
            ),
        );
    }

    /**
     * @dataProvider getItemizedListTypes
     */
    public function testRenderItemizedListTypes( $type, array $items )
    {
        $mock = $this->getMock( 'ezcTestDocumentPdfMockDriver', array(
            'createPage',
            'drawWord',
        ) );

        // Expectations
        $mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 18, 1. ), $this->equalTo( $items[0] )
        );
        $mock->expects( $this->at( 4 ) )->method( 'drawWord' )->with(
            $this->equalTo( 10, 1. ), $this->equalTo( 33, 1. ), $this->equalTo( $items[1] )
        );

        $docbook = new ezcDocumentDocbook();
        $docbook->loadFile( dirname( __FILE__ ) . '/../files/pdf/bullet_list.xml' );

        // Set numeration type in document
        $dom   = $docbook->getDomDocument();
        $xpath = new DOMXPath( $dom );
        $xpath->registerNamespace( 'doc', 'http://docbook.org/ns/docbook' );
        $list  = $xpath->evaluate( '//doc:itemizedlist' )->item( 0 );
        $list->setAttribute( 'mark', $type );
        $docbook->setDomDocument( $dom );

        $renderer  = new ezcDocumentPdfMainRenderer( $mock, $this->styles );
        $pdf = $renderer->render(
            $docbook,
            new ezcDocumentPdfDefaultHyphenator()
        );
    }
}

?>

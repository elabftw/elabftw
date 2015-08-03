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
class ezcDocumentPdfImageHandlerTests extends ezcDocumentPdfTestCase
{
    protected $document;
    protected $xpath;
    protected $styles;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testImageHandler()
    {
        $image = ezcDocumentPdfImage::createFromFile( dirname(  __FILE__ ) . '/../files/pdf/images/logo-white.png' );

        $this->assertSame(
            'image/png',
            $image->getMimeType()
        );
        $this->assertEquals(
            array( new ezcDocumentPcssMeasure( '113px' ), new ezcDocumentPcssMeasure( '57px' ) ),
            $image->getDimensions()
        );
    }

    public static function provideCanHandleData()
    {
        return array(
            array( 'files/pdf/images/logo-white.eps', false ),
            array( 'files/pdf/images/logo-white.pdf', false ),
            array( 'files/pdf/images/logo-white.png', true ),
            array( 'files/pdf/images/logo-white.svg', false ),
            array( 'files/pdf/images/logo-white.jpeg', true ),
        );
    }

    /**
     * @dataProvider provideCanHandleData
     */
    public function testCanHandleImageType( $image, $return )
    {
        $handler = new ezcDocumentPdfPhpImageHandler();
        $this->assertSame( $return, $handler->canHandle( dirname( __FILE__ ) . '/../' . $image ) );
    }

    public static function provideDimensionData()
    {
        return array(
            array( 'files/pdf/images/logo-white.eps', false ),
            array( 'files/pdf/images/logo-white.pdf', false ),
            array( 'files/pdf/images/logo-white.png', array( new ezcDocumentPcssMeasure( '113px' ), new ezcDocumentPcssMeasure( '57px' ) ) ),
            array( 'files/pdf/images/logo-white.svg', false ),
            array( 'files/pdf/images/logo-white.png', array( new ezcDocumentPcssMeasure( '113px' ), new ezcDocumentPcssMeasure( '57px' ) ) ),
        );
    }

    /**
     * @dataProvider provideDimensionData
     */
    public function testImageDimensions( $image, $return )
    {
        $handler = new ezcDocumentPdfPhpImageHandler();
        $this->assertEquals( $return, $handler->getDimensions( dirname( __FILE__ ) . '/../' . $image ) );
    }

    public static function provideMimeTypeData()
    {
        return array(
            array( 'files/pdf/images/logo-white.eps', false ),
            array( 'files/pdf/images/logo-white.pdf', false ),
            array( 'files/pdf/images/logo-white.png', 'image/png' ),
            array( 'files/pdf/images/logo-white.svg', false ),
            array( 'files/pdf/images/logo-white.jpeg', 'image/jpeg' ),
        );
    }

    /**
     * @dataProvider provideMimeTypeData
     */
    public function testImageMimeType( $image, $return )
    {
        $handler = new ezcDocumentPdfPhpImageHandler();
        $this->assertSame( $return, $handler->getMimeType( dirname( __FILE__ ) . '/../' . $image ) );
    }
}

?>

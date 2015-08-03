<?php
/**
 * ezcDocumentPdfHyphenatorTests
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

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfPageTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testEmptyPageFixedBlock()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testOnPageBoundings()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 0, 100, 100 ),
            $page->testFitRectangle( 0, 0, 100, 100 )
        );
    }

    public function testOutOfPageBoundingsX()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            false,
            $page->testFitRectangle( -10, 10, 80, 80 )
        );
    }

    public function testOutOfPageBoundingsY()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, -10, 80, 80 )
        );
    }

    public function testOutOfPageBoundingsWidth()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 95, 80 )
        );
    }

    public function testOutOfPageBoundingsHeight()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 95 )
        );
    }

    public function testCoveredAreaNoIntersection()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 20 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 30, 80, 60 ),
            $page->testFitRectangle( 10, 30, 80, 60 )
        );
    }

    public function testCoveredAreaOnLineNoIntersection()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 20 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 20, 80, 70 ),
            $page->testFitRectangle( 10, 20, 80, 70 )
        );
    }

    public function testCoveredAreaIntersectionXIn()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaIntersectionXInSecond()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 1, 1 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaIntersectionYIn()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 20 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaIntersectionWidthIn()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 80, 0, 20, 100 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaIntersectionHeightIn()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 80, 100, 20 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaIntersectionInnerBox()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 20, 20, 60, 60 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaExcatMatch()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, 10, 80, 80 )
        );
    }

    public function testCoveredAreaHorizontalMovingImpossible()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( null, 10, 20, 80 )
        );
    }

    public function testCoveredAreaHorizontalMoving()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 20, 10, 10, 80 ),
            $page->testFitRectangle( null, 10, 10, 80 )
        );
    }

    public function testCoveredAreaHorizontalMovingOutOfPage()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( null, 10, 90, 80 )
        );
    }

    public function testCoveredAreaHorizontalMovingOutIntoBox()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 80, 0, 20, 100 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( null, 10, 70, 80 )
        );
    }

    public function testCoveredAreaVerticalMoving()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 20 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 20, 80, 10 ),
            $page->testFitRectangle( 10, null, 80, 10 )
        );
    }

    public function testCoveredAreaVerticalMovingOutOfPage()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 20 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, null, 80, 90 )
        );
    }

    public function testCoveredAreaVerticalMovingOutIntoBox()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 80, 100, 20 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 20 ) );
        $this->assertEquals(
            false,
            $page->testFitRectangle( 10, null, 80, 70 )
        );
    }

    public function testCoveredAreaBidirectionalMove1()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 10, 10 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( null, null, 80, 80 )
        );
    }

    public function testCoveredAreaBidirectionalMove2()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 10 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( null, null, 80, 80 )
        );
    }

    public function testHorizontalFullPageExtension()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 0, 100, 100 ),
            $page->testFitRectangle( 0, 0, null, 100 )
        );
    }

    public function testCoveredBoxHorizontalExtension1()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 90, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 0, 90, 100 ),
            $page->testFitRectangle( 0, 0, null, 100 )
        );
    }

    public function testCoveredBoxHorizontalExtension2()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 50, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 0, 50, 100 ),
            $page->testFitRectangle( 0, 0, null, 100 )
        );
    }

    public function testCoveredBoxHorizontalExtension3()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 90, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( 10, 10, null, 80 )
        );
    }

    public function testCoveredBoxVerticalExtension1()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 90, 100, 10 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 0, 100, 90 ),
            $page->testFitRectangle( 0, 0, 100, null )
        );
    }

    public function testCoveredBoxVerticalExtension2()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 50, 100, 10 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 0, 100, 50 ),
            $page->testFitRectangle( 0, 0, 100, null )
        );
    }

    public function testCoveredBoxVerticalExtension3()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 90, 100, 10 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( 10, 10, 80, null )
        );
    }

    public function testCoveredBoxBiderectionalExtension1()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 90, 90, 10, 10 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( 10, 10, null, null )
        );
    }

    public function testCoveredBoxBiderectionalExtension2()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 90, 100, 10 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 90, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( 10, 10, null, null )
        );
    }

    public function testCoveredBoxExtensionAndOrthogonalMovement1()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 10 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 10, 100 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 90, 100, 10 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 90, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( 10, null, null, 80 )
        );
    }

    public function testCoveredBoxExtensionAndOrthogonalMovement2()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 100, 10 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 10, 100 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 90, 100, 10 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 90, 0, 10, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 10, 80, 80 ),
            $page->testFitRectangle( null, 10, 80, null )
        );
    }

    public function testMoveAndExtendInSameDirection1()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );

        try
        {
            $page->testFitRectangle( null, 10, null, 10 );
            $this->fail( 'Expected ezcBaseFunctionalityNotSupportedException' );
        }
        catch ( ezcBaseFunctionalityNotSupportedException $e )
        { /* Expected */ }
    }

    public function testMoveAndExtendInSameDirection2()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );

        try
        {
            $page->testFitRectangle( 10, null, 10, null );
            $this->fail( 'Expected ezcBaseFunctionalityNotSupportedException' );
        }
        catch ( ezcBaseFunctionalityNotSupportedException $e )
        { /* Expected */ }
    }

    public function testMoveAndExtendInSameDirection3()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );

        try
        {
            $page->testFitRectangle( null, null, null, null );
            $this->fail( 'Expected ezcBaseFunctionalityNotSupportedException' );
        }
        catch ( ezcBaseFunctionalityNotSupportedException $e )
        { /* Expected */ }
    }

    public function testCoveredAreasInDifferentTransactions()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->startTransaction( 1 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 80, 0, 20, 100 ) );
        $page->startTransaction( 2 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 20, 10, 60, 80 ),
            $page->testFitRectangle( null, 10, 60, 80 )
        );
    }

    public function testCoveredAreasWithRevertedTransaction()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->startTransaction( 1 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 80, 0, 20, 100 ) );
        $page->startTransaction( 2 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $page->revert( 2 );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 10, 60, 80 ),
            $page->testFitRectangle( null, 10, 60, 80 )
        );
    }

    public function testCoveredAreasWithMultipleRevertedTransactions()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->startTransaction( 1 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 80, 0, 20, 100 ) );
        $page->startTransaction( 2 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 20, 100 ) );
        $page->revert( 1 );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 0, 10, 100, 80 ),
            $page->testFitRectangle( null, 10, 100, 80 )
        );
    }

    public function testUncoverArea()
    {
        $page = new ezcDocumentPdfPage( 1, 100, 100 );
        $page->startTransaction( 1 );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 0, 50, 50 ) );
        $id = $page->setCovered( new ezcDocumentPdfBoundingBox( 0, 50, 50, 50 ) );
        $page->setCovered( new ezcDocumentPdfBoundingBox( 50, 0, 50, 50 ) );
        $this->assertEquals( false, $page->testFitRectangle( 10, 60, 30, 30 ) );

        $page->uncover( $id );
        $this->assertEquals(
            new ezcDocumentPdfBoundingBox( 10, 60, 30, 30 ),
            $page->testFitRectangle( 10, 60, 30, 30 )
        );
    }
}
?>

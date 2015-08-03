<?php
/**
 * ezcDocumentPdfTransactionalDriverWrapperTests
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
class ezcDocumentPdfTransactionalDriverWrapperTests extends ezcDocumentPdfTestCase
{
    protected $driver;

    protected $mock;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->driver = new ezcDocumentPdfTransactionalDriverWrapper();
        $this->driver->setDriver(
            $this->mock = $this->getMock( 'ezcDocumentPdfDriver', array(
                'createPage',
                'getCurrentLineHeight',
                'calculateWordWidth',
                'setTextFormatting',
                'drawWord',
                'drawImage',
                'drawRectangle',
                'save',
            ) )
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->mock = null;
        $this->driver = null;
    }

    public function testNoIssuedWriteCallsToBackend()
    {
        $this->mock->expects( $this->never() )->method( 'drawWord' );

        // Cause and record calls
        $this->driver->drawWord( 0, 0, 'Hello world.' );
    }

    public function testPassReadCallsToBackend()
    {
        $this->mock->expects( $this->never() )->method( 'drawWord' );
        $this->mock->expects( $this->once() )->method( 'calculateWordWidth' );

        // Cause and record calls
        $this->driver->calculateWordWidth( 'Hello world.' );
        $this->driver->drawWord( 0, 0, 'Hello world.' );
    }

    public function testPassCombinedReadWriteCallToBackend()
    {
        $this->mock->expects( $this->once() )->method( 'setTextFormatting' );

        // Cause and record calls
        $this->driver->setTextFormatting( 'font-size', '12pt' );
    }

    public function testCommitSingleTransaction()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 44, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'are' )
        );

        // Cause and record calls
        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->commit( $transaction );
    }

    public function testImplicitCommitMultipleTransactions()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 44, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'are' )
        );
        $this->mock->expects( $this->exactly( 2 ) )->method( 'drawWord' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->startTransaction();
        $this->driver->drawWord( 60, 0, 'separated' );
        $this->driver->commit( $transaction );
    }

    public function testCommitAll()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 44, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'are' )
        );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->commit();
    }

    public function testCommitUnknownTransaction()
    {
        $this->mock->expects( $this->never() )->method( 'drawWord' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->commit( 'unknown id' );
    }

    public function testRevertTransactions()
    {
        $this->mock->expects( $this->never() )->method( 'drawWord' );

        // Cause and record calls
        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->revert( $transaction );
        $this->driver->commit();
    }

    public function testPartialRevertTransactions()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->exactly( 1 ) )->method( 'drawWord' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->revert( $transaction );
        $this->driver->commit();
    }

    public function testRevertMultipleTransactions()
    {
        $this->mock->expects( $this->never() )->method( 'drawWord' );

        // Cause and record calls
        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->revert( $transaction );
        $this->driver->commit();
    }

    public function testAutoCommitOnSave()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'save' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $this->driver->save();
    }

    public function testCreatePageCall()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 100, 1. ), $this->equalTo( 100, 1. )
        );
        $this->mock->expects( $this->exactly( 1 ) )->method( 'createPage' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->createPage( 100, 100 );
        $this->driver->save();
    }

    public function testSetTextFormattingCall()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'setTextFormatting' )->with(
            $this->equalTo( 'font-size' ), $this->equalTo( '12pt' )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'setTextFormatting' )->with(
            $this->equalTo( 'font-size' ), $this->equalTo( '12pt' )
        );
        $this->mock->expects( $this->exactly( 2 ) )->method( 'setTextFormatting' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->driver->setTextFormatting( 'font-size', '12pt' );
        $this->driver->save();
    }

    public function testCalculateWordWidthCall()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'calculateWordWidth' )->with(
            $this->equalTo( 'my word' )
        )->will( $this->returnValue( 12 ) );
        $this->mock->expects( $this->exactly( 1 ) )->method( 'calculateWordWidth' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->assertEquals(
            12,
            $this->driver->calculateWordWidth( 'my word' )
        );
        $this->driver->save();
    }

    public function testGetLineHeightCall()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'getCurrentLineHeight' )
            ->will( $this->returnValue( 12 ) );
        $this->mock->expects( $this->exactly( 1 ) )->method( 'getCurrentLineHeight' );

        // Cause and record calls
        $this->driver->startTransaction();
        $this->assertEquals(
            12,
            $this->driver->getCurrentLineHeight()
        );
        $this->driver->save();
    }

    public function testPageLevelTransactions1()
    {
        $this->mock->expects( $this->never() )->method( 'drawWord' );

        // Cause and record calls
        $transaction = $this->driver->startTransaction();
        $page1 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 0, 0, 'Paragraphs' );

        $this->assertSame( $page1, $this->driver->currentPage() );

        $page2 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 44, 0, 'are' );

        $this->assertNotSame( $page1, $page2 );
        $this->assertSame( $page2, $this->driver->currentPage() );

        $this->driver->revert( $transaction );
        $this->driver->commit();

        $this->assertSame( null, $this->driver->currentPage() );
    }

    public function testPageLevelTransactions2()
    {
        $this->mock->expects( $this->once() )->method( 'drawWord' );
        $this->mock->expects( $this->once() )->method( 'createPage' );

        // Cause and record calls
        $page1 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 0, 0, 'Paragraphs' );

        $this->assertSame( $page1, $this->driver->currentPage() );

        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->revert( $transaction );
        $this->driver->commit();

        $this->assertSame( $page1, $this->driver->currentPage() );
    }

    public function testPageLevelTransactions3()
    {
        $this->mock->expects( $this->once() )->method( 'drawWord' );
        $this->mock->expects( $this->once() )->method( 'createPage' );

        // Cause and record calls
        $page1 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 0, 0, 'Paragraphs' );

        $this->assertSame( $page1, $this->driver->currentPage() );

        $transaction = $this->driver->startTransaction();
        $page2 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 44, 0, 'are' );

        $this->assertNotSame( $page1, $page2 );
        $this->assertSame( $page2, $this->driver->currentPage() );

        $this->driver->revert( $transaction );
        $this->driver->commit();

        $this->assertSame( $page1, $this->driver->currentPage() );
    }

    public function testPageLevelTransactions4()
    {
        $this->mock->expects( $this->once() )->method( 'drawWord' );

        // Cause and record calls
        $page1 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 0, 0, 'Paragraphs' );

        $this->assertSame( $page1, $this->driver->currentPage() );

        $page2 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );

        $this->assertNotSame( $page1, $page2 );
        $this->assertSame( $page2, $this->driver->currentPage() );

        $transaction = $this->driver->startTransaction();
        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->revert( $transaction );
        $this->driver->commit();

        $this->assertSame( $page2, $this->driver->currentPage() );
    }

    public function testPageLevelTransactionsGoBack1()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 210, 1. ), $this->equalTo( 297, 1. )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->at( 2 ) )->method( 'drawWord' )->with(
            $this->equalTo( 44, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'are' )
        );
        $this->mock->expects( $this->at( 3 ) )->method( 'createPage' )->with(
            $this->equalTo( 210, 1. ), $this->equalTo( 297, 1. )
        );

        // Cause and record calls
        $page1 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $page2 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->goBackOnePage();

        $this->assertSame( $page1, $this->driver->currentPage() );

        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->commit();
    }

    public function testPageLevelTransactionsGoBack2()
    {
        $this->mock->expects( $this->at( 0 ) )->method( 'createPage' )->with(
            $this->equalTo( 210, 1. ), $this->equalTo( 297, 1. )
        );
        $this->mock->expects( $this->at( 1 ) )->method( 'drawWord' )->with(
            $this->equalTo( 0, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'Paragraphs' )
        );
        $this->mock->expects( $this->at( 2 ) )->method( 'drawWord' )->with(
            $this->equalTo( 44, 1. ), $this->equalTo( 0, 1. ), $this->equalTo( 'are' )
        );
        $this->mock->expects( $this->at( 3 ) )->method( 'createPage' )->with(
            $this->equalTo( 210, 1. ), $this->equalTo( 297, 1. )
        );

        // Cause and record calls
        $page1 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->drawWord( 0, 0, 'Paragraphs' );
        $page2 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->driver->goBackOnePage();

        $transaction = $this->driver->startTransaction();
        $page3 = $this->driver->appendPage( new ezcDocumentPcssStyleInferencer() );
        $this->assertSame( $page2, $page3 );
        $this->driver->drawWord( 44, 0, 'left' );
        $this->driver->revert( $transaction );

        $this->assertSame( $page1, $this->driver->currentPage() );

        $this->driver->drawWord( 44, 0, 'are' );
        $this->driver->commit();
    }
}

?>

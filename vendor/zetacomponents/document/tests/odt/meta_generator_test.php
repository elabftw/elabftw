<?php
/**
 * ezcDocumentOdtMetaGeneratorTest.
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
class ezcDocumentOdtMetaGeneratorTest extends ezcTestCase
{
    protected $domElement;

    protected $generator;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    protected function setup()
    {
        $dom = new DOMDocument();
        $this->domElement = $dom->appendChild(
            $dom->createElementNS(
                ezcDocumentOdt::NS_ODT_OFFICE,
                'office:meta'
            )
        );

        $this->generator = new ezcDocumentOdtMetaGenerator();
    }

    public function testGenerateGenerator()
    {
        $this->generator->generateMetaData( $this->domElement );

        $generatorTags = $this->domElement->getElementsByTagnameNS(
            ezcDocumentOdt::NS_ODT_META,
            'generator'
        );

        $this->assertEquals(
            1,
            $generatorTags->length
        );

        $this->assertEquals(
            'eZComponents/Document-dev',
            $generatorTags->item( 0 )->nodeValue
        );
    }

    public function testGenerateMetaDate()
    {
        $this->generator->generateMetaData( $this->domElement );

        $dateTags = $this->domElement->getElementsByTagnameNS(
            ezcDocumentOdt::NS_ODT_META,
            'creation-date'
        );

        $this->assertEquals(
            1,
            $dateTags->length
        );

        $actDate = new DateTime( $dateTags->item( 0 )->nodeValue );
        $curDate = new DateTime();

        $this->assertEquals(
            $curDate->format( 'Ymd' ),
            $actDate->format( 'Ymd' )
        );
    }

    public function testGenerateDcDate()
    {
        $this->generator->generateMetaData( $this->domElement );

        $dateTags = $this->domElement->getElementsByTagnameNS(
            ezcDocumentOdt::NS_DC,
            'date'
        );

        $this->assertEquals(
            1,
            $dateTags->length
        );

        $actDate = new DateTime( $dateTags->item( 0 )->nodeValue );
        $curDate = new DateTime();

        $this->assertEquals(
            $curDate->format( 'Ymd' ),
            $actDate->format( 'Ymd' )
        );
    }
}

?>

<?php
/**
 * ezcDocumentPdfDriverHaruTests
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

require_once 'driver_tests.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfTableColumnWidthCalculatorTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getTableColumnWidths()
    {
        return array(
            array(
                'simple_tables.xml',
                '//doc:table[1]',
                array( .314, .314, .372 ),
            ),
            array(
                'simple_tables.xml',
                '//doc:table[2]',
                array( .317, .317, .366 ),
            ),
            array(
                'tables_with_list.xml',
                '//doc:table[1]',
                array( .377, .623 ),
            ),
            array(
                'stacked_table.xml',
                '//doc:table[1]',
                array( .236, .236, .528 ),
            ),
            array(
                'irregular_tables_1.xml',
                '//doc:table[1]',
                array( .129, .871 ),
            ),
            array(
                'irregular_tables_2.xml',
                '//doc:table[1]',
                array( .5, .5 ),
            ),
        );
    }

    /**
     * @dataProvider getTableColumnWidths
     */
    public function testTableColumnWidthEstimation( $file, $query, $expectation )
    {
        $doc = new DOMDocument();
        $doc->load( dirname( __FILE__ ) . '/../files/pdf/' . $file );

        $xpath = new DOMXPath( $doc );
        $xpath->registerNamespace( 'doc', 'http://docbook.org/ns/docbook' );
        $table = $xpath->query( $query )->item( 0 );

        $calculator = new ezcDocumentPdfDefaultTableColumnWidthCalculator();
        $this->assertEquals(
            $expectation,
            $calculator->estimateWidths( $table ),
            'Wrong table width estimations',
            .001
        );
    }
}

?>

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
class ezcDocumentPdfDriverSvgTests extends ezcDocumentPdfDriverTests
{
    /**
     * Extension of generated files
     * 
     * @var string
     */
    protected $extension = 'svg';

    /**
     * Expected font widths for calculateWordWidth tests
     * 
     * @var array
     */
    protected $expectedWidths = array(
        'testEstimateDefaultWordWidthWithoutPageCreation' => 21.6,
        'testEstimateDefaultWordWidth'                    => 21.6,
        'testEstimateWordWidthDifferentSize'              => 30.1,
        'testEstimateWordWidthDifferentSizeAndUnit'       => 10.6,
        'testEstimateBoldWordWidth'                       => 21.6,
        'testEstimateMonospaceWordWidth'                  => 25.8,
        'testFontStyleFallback'                           => 21.6,
        'testUtf8FontWidth'                               => 21.6,
        'testCustomFontWidthEstimation'                   => 51.6,
    );

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    /**
     * Get driver to test
     * 
     * @return ezcDocumentPdfDriver
     */
    protected function getDriver()
    {
        return new ezcDocumentPdfSvgDriver();
    }
}

?>

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

require_once 'driver_tests.php';

// Try to include TCPDF class from external/tcpdf.
// @TODO: Maybe also search the include path...
if ( file_exists( $path = dirname( __FILE__ ) . '/../external/tcpdf-4.8/tcpdf.php' ) )
{
    include $path;
}

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentPdfDriverTcpdfTests extends ezcDocumentPdfDriverTests
{
    /**
     * Old error reporting level restored after the test
     * 
     * @var int
     */
    protected $oldErrorReporting;

    /**
     * Name of the driver class to test
     * 
     * @var string
     */
    protected $driverClass = 'ezcDocumentPdfTcpdfDriver';

    /**
     * Expected font widths for calculateWordWidth tests
     * 
     * @var array
     */
    protected $expectedWidths = array(
        'testEstimateDefaultWordWidthWithoutPageCreation' => 9.6,
        'testEstimateDefaultWordWidth'                    => 9.6,
        'testEstimateWordWidthDifferentSize'              => 31.9,
        'testEstimateWordWidthDifferentSizeAndUnit'       => 11.3,
        'testEstimateBoldWordWidth'                       => 10.4,
        'testEstimateMonospaceWordWidth'                  => 36,
        'testFontStyleFallback'                           => 16.3,
        'testUtf8FontWidth'                               => 11.8,
        'testCustomFontWidthEstimation'                   => null,
    );

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        parent::setUp();

        // Change error reporting - this is evil, but otherwise TCPDF will
        // abort the tests, because it throws lots of E_NOTICE and
        // E_DEPRECATED.
        $this->oldErrorReporting = error_reporting( E_PARSE | E_ERROR | E_WARNING );
    }

    public function tearDown()
    {
        error_reporting( $this->oldErrorReporting );
        parent::tearDown();
    }

    /**
     * Get driver to test
     * 
     * @return ezcDocumentPdfDriver
     */
    protected function getDriver()
    {
        if ( !class_exists( 'TCPDF' ) )
        {
            $this->markTestSkipped( 'This test requires the TCPDF class.' );
        }

        return new ezcDocumentPdfTcpdfDriver();
    }
}

?>

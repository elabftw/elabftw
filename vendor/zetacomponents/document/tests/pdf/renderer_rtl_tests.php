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
class ezcDocumentPdfRenderRtlTests extends ezcDocumentPdfTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testRenderAllRtl()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/long_text.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array(
                new ezcDocumentPcssLayoutDirective(
                    array( 'article' ),
                    array(
                        'direction' => 'rtl',
                    )
                ),
            )
        );
    }

    public function testRenderParagraphRtl()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/long_text.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array(
                new ezcDocumentPcssLayoutDirective(
                    array( 'para' ),
                    array(
                        'direction' => 'rtl',
                    )
                ),
            )
        );
    }

    public function testRenderTitleRtl()
    {
        $this->renderFullDocument(
            dirname( __FILE__ ) . '/../files/pdf/long_text.xml',
            __CLASS__ . '_' . __FUNCTION__ . '.svg',
            array(
                new ezcDocumentPcssLayoutDirective(
                    array( 'title' ),
                    array(
                        'direction' => 'rtl',
                    )
                ),
            )
        );
    }
}

?>

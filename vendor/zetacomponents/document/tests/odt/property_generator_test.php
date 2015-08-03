<?php
/**
 * ezcDocumentOdtFormattingPropertiesTest.
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
abstract class ezcDocumentOdtStylePropertyGeneratorTest extends ezcTestCase
{
    protected $styleConverters;

    public function setup()
    {
        $this->styleConverters = new ezcDocumentOdtPcssConverterManager();
    }

    protected function getDomElementFixture()
    {
        $domDocument = new DOMDocument();
        return $domDocument->appendChild(
             $domDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_STYLE,
                'style'
             )
        );
    }
    
    protected function assertPropertyExists( $exptectedNs, $expectedName, array $expectedProperties, DOMElement $actualParent )
    {
        $props = $actualParent->getElementsByTagNameNS(
            $exptectedNs,
            $expectedName
        );
        $this->assertEquals( 1, $props->length );

        $prop = $props->item( 0 );

        foreach ( $expectedProperties as $propDef )
        {
            $this->assertTrue(
                $prop->hasAttributeNs(
                    $propDef[0],
                    $propDef[1]
                )
            );
        }
    }
}

?>

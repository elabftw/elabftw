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
class ezcDocumentOdtFormattingPropertiesTest extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testConstructorSuccess()
    {
        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        $this->assertAttributeEquals(
            array(
                'type' => ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
            ),
            'properties',
            $props
        );
    }
    
    public function testAppendValueFailure()
    {
        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        try
        {
            $props->append( 'foo' );
            $this->fail( 'Exception not thrown on invalid method call to append().' );
        }
        catch ( RuntimeException $e ) {}
    }
    
    public function testExchangeArrayFailure()
    {
        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        try
        {
            $props->exchangeArray( array() );
            $this->fail( 'Exception not thrown on invalid method call to exchangeArray().' );
        }
        catch ( RuntimeException $e ) {}
    }

    public function testOffsetSetSuccess()
    {
        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        $props['foo'] = 23;

        $this->assertEquals(
            23,
            $props['foo']
        );
    }

    public function testOffsetSetFailure()
    {
        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        try
        {
            $props[23] = 'foo';
            $this->fail( 'Exception not thrown on invalid offset 23.' );
        }
        catch ( ezcBaseValueException $e ) {}
    }
}

?>

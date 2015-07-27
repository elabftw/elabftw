<?php
/**
 * ezcDocumentOdtFormattingPropertyCollectionTest.
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
class ezcDocumentOdtFormattingPropertyCollectionTest extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        $this->propColl = new ezcDocumentOdtFormattingPropertyCollection();
    }

    public function tearDown()
    {
        unset( $this->propColl );
    }

    public function testConstructorSuccess()
    {
        $this->assertAttributeEquals(
            array(),
            'properties',
            $this->propColl
        );
    }

    public function testSetPropertiesSuccess()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );
        $this->propColl->setProperties(
            $props
        );

        $this->assertAttributeEquals(
            array(
                ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT => $props
            ),
            'properties',
            $this->propColl
        );
    }

    public function testSetPropertiesFailure()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );
        $this->propColl->setProperties(
            $props
        );

        try
        {
            $this->propColl->setProperties( $props );
            $this->fail( 'Exception not thrown on double setting of properties.' );
        }
        catch ( ezcDocumentOdtFormattingPropertiesExistException $e ) {}
    }

    public function testReplacePropertiesSuccessSingle()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );
        $this->propColl->replaceProperties(
            $props
        );

        $this->assertAttributeEquals(
            array(
                ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT => $props
            ),
            'properties',
            $this->propColl
        );
    }

    public function testReplacePropertiesSuccessDouble()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );
        $this->propColl->replaceProperties(
            $props
        );
        $this->propColl->replaceProperties(
            $props
        );

        $this->assertAttributeEquals(
            array(
                ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT => $props
            ),
            'properties',
            $this->propColl
        );
    }

    public function testHasPropertiesSuccess()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );
        $this->propColl->setProperties(
            $props
        );

        $this->assertTrue(
            $this->propColl->hasProperties( ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT )
        );
    }

    public function testHasPropertiesFailure()
    {
        $this->assertFalse(
            $this->propColl->hasProperties( ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT )
        );
    }

    public function testGetPropertiesSuccess()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );
        $this->propColl->setProperties(
            $props
        );

        $this->assertSame(
            $props,
            $this->propColl->getProperties( ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT )
        );
    }

    public function testGetPropertiesFailure()
    {

        $props = new ezcDocumentOdtFormattingProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        $this->assertNull(
            $this->propColl->getProperties( ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT )
        );
    }
}

?>

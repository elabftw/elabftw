<?php
/**
 * ezcDocumentPdfStyleInferenceTests
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
class ezcDocumentPcssLocationIdTests extends ezcTestCase
{
    protected $document;
    protected $xpath;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function setUp()
    {
        $this->document = new DOMDocument();
        $this->document->registerNodeClass( 'DOMElement', 'ezcDocumentLocateableDomElement' );

        $this->document->load( dirname( __FILE__ ) . '/../files/docbook/pdf/location_ids.xml' );

        $this->xpath = new DOMXPath( $this->document );
        $this->xpath->registerNamespace( 'doc', 'http://docbook.org/ns/docbook' );
    }

    public function testRootNodeLocationId()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $this->assertEquals(
            '/article',
            $element->getLocationId()
        );
    }

    public function testSectionNodeLocationId()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $this->assertEquals(
            '/article/section#paragraph_with_inline_markup',
            $element->getLocationId()
        );
    }

    public function testLocationIdFromStrangeElementId()
    {
        $element = $this->xpath->query( '//doc:sectioninfo' )->item( 0 );

        $this->assertEquals(
            '/article/section#paragraph_with_inline_markup/sectioninfo#some_strange_id_42',
            $element->getLocationId()
        );
    }

    public function testNodeLocationIdWithRole()
    {
        $element = $this->xpath->query( '//doc:emphasis' )->item( 1 );

        $this->assertEquals(
            '/article/section#paragraph_with_inline_markup/para/emphasis[Role=strong]',
            $element->getLocationId()
        );
    }

    public function testNodeLocationIdWithClass()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 1 );

        $this->assertEquals(
            '/article/section#paragraph_with_inline_markup/para.note_warning',
            $element->getLocationId()
        );
    }

    public function testNodeLocationIdWithRoleNormalization()
    {
        $element = $this->xpath->query( '//doc:emphasis' )->item( 2 );

        $this->assertEquals(
            '/article/section#paragraph_with_inline_markup/para.note_warning/emphasis[Role=strong]',
            $element->getLocationId()
        );
    }
}

?>

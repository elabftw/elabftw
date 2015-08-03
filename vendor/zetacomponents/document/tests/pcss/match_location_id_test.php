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
class ezcDocumentPcssMatchLocationIdTests extends ezcTestCase
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

    public function testMatchCommonRootNode()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testMatchExplicitRootNode()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '> article' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testNoMatchExplicitRootNode()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '> section' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testNotMatchChildWithParentAssertion()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testNoMatchRequiredId()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', '#some_id' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testNoMatchRequiredClass()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', '.class' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testNoMatchRequiredClassAndId()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', '.class', '#some_id' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testMatchNodeWithId()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'section', '#paragraph_with_inline_markup' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testMatchAnyDescendant()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', 'section' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testMatchDirectDescendant()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', 'section' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testMatchAnyDescendentIgnoreId()
    {
        $element = $this->xpath->query( '//doc:sectioninfo' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', 'sectioninfo' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testNotMatchDirectDescendent()
    {
        $element = $this->xpath->query( '//doc:sectioninfo' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'article', '> sectioninfo' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testNotMatchPartialId()
    {
        $element = $this->xpath->query( '//doc:sectioninfo' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'section', '#paragraph' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testMatchByClassName()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 1 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'para', '.note_warning' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testMatchByPartialClassName()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 1 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'para', '.note' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testMatchByPartialClassName2()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 1 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'para', '.warning' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testNotMatchByPartialClassName()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 1 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( 'para', '.not' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testMatchOnlyByClassName()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 1 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '.note' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testNotMatchOnlyByClassName()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '.note' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testMatchOnlyById()
    {
        $element = $this->xpath->query( '//doc:section' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '#paragraph_with_inline_markup' ),
            array()
        );

        $this->assertEquals(
            true,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to match elements location id: \"$id\"."
        );
    }

    public function testNotMatchOnlyById()
    {
        $element = $this->xpath->query( '//doc:article' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '#paragraph_with_inline_markup' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }

    public function testNotMatchChildOnlyById()
    {
        $element = $this->xpath->query( '//doc:para' )->item( 0 );

        $directive = new ezcDocumentPcssLayoutDirective(
            array( '#paragraph_with_inline_markup' ),
            array()
        );

        $this->assertEquals(
            false,
            (bool) preg_match( $regexp = $directive->getRegularExpression(), $id = $element->getLocationId() ),
            "Directive $regexp was expected to NOT match elements location id: \"$id\"."
        );
    }
}

?>

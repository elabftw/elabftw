<?php
/**
 * File containing the ezcDocumentOdtStyleInferencer class.
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */

/**
 * An instance of this class inferences a style for an ODT element.
 *
 * An instance of this class parses the styles of an ODT document defined for a 
 * certain element and returns an object representation of this style.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtStyleInferencer
{
    /**
     * ODT DOMDocument.
     * 
     * @var DOMDocument
     */
    protected $odtDocument;

    /**
     * Style extractor. 
     * 
     * @var ezcDocumentOdtStyleExtractor
     */
    protected $styleExtractor;

    /**
     * Style parser.
     * 
     * @var ezcDocumentOdtStyleParser
     */
    protected $styleParser;

    /**
     * Maps ODT DOMElements to style families.
     * 
     * @var array(string=>array(string=>const))
     */
    protected $styleFamilyMap = array(
        ezcDocumentOdt::NS_ODT_TEXT => array(
            'h'    => ezcDocumentOdtStyle::FAMILY_PARAGRAPH,
            'p'    => ezcDocumentOdtStyle::FAMILY_PARAGRAPH,
            'span' => ezcDocumentOdtStyle::FAMILY_TEXT,
        )
    );

    /**
     * Maps ODT namespaces to style name attributes 
     * 
     * @var array(const=>array(const,string))
     */
    protected $styleNameAttributeMap = array(
        ezcDocumentOdt::NS_ODT_TEXT => array(
            'namespace' => ezcDocumentOdt::NS_ODT_TEXT,
            'attribute' => 'style-name'
        ),
    );

    /**
     * Create a new style inferencer for the given document.
     * 
     * @param DOMDocument $odtDocument 
     */
    public function __construct( DOMDocument $odtDocument )
    {
        $this->odtDocument    = $odtDocument;
        $this->styleExtractor = new ezcDocumentOdtStyleExtractor( $odtDocument );
        $this->styleParser    = new ezcDocumentOdtStyleParser();
    }

    /**
     * Returns the style for the given $odtElement.
     *
     * Inferences the complete styling information for the given $odtElement.
     * 
     * @param DOMElement $odtElement 
     * @return ezcDocumentOdtStyle
     */
    public function getStyle( DOMElement $odtElement )
    {
        $family = $this->getStyleFamily( $odtElement );
        $name   = $this->getStyleName( $odtElement );

        $styleDom = $this->styleExtractor->extractStyle( $family, $name );
        // @todo: Inference parent / default styles

        return $this->styleParser->parseStyle( $styleDom, $family, $name );
    }

    /**
     * Returns the list-style for the given $odtElement. 
     *
     * $odtElement must be a <list /> element, otherwise null is returned since 
     * other elements do not have a list style attached.
     * 
     * @param DOMElement $odtElement 
     * @return ezcDocumentOdtListStyle|null
     * @todo Paragraphs may have a list style defined in their properties, 
     *       which is used by default for new lists created in the paragraph. 
     *       This is actually not significant for us, since lists do always 
     *       have a dedicated style attached. Anyway, we might want to include 
     *       it sometimes.
     */
    public function getListStyle( DOMElement $odtElement )
    {
        $name = $this->getListStyleName( $odtElement );

        $styleDom = $this->styleExtractor->extractListStyle( $name );

        return $this->styleParser->parseListStyle( $styleDom, $name );
    }

    /**
     * Extracts the style family from $odtElement.
     *
     * Detects the style family the style for $odtElement resides in.
     * 
     * @param DOMElement $odtElement 
     * @return string
     */
    protected function getStyleFamily( DOMElement $odtElement )
    {
        if ( !isset( $this->styleFamilyMap[$odtElement->namespaceURI][$odtElement->localName] ) )
        {
            throw new RuntimeException( "Could not map style family for element '{$odtElement->localName}' in namespace '{$odtElement->namespaceURI}'." );
        }
        return $this->styleFamilyMap[$odtElement->namespaceURI][$odtElement->localName];
    }

    /**
     * Extracts the style name from the given $odtElement.
     *
     * Tries to determine the correct attribute for the style name from the 
     * given $odtElement. If a style name is specified, it is returned. 
     * Otherwise null is returned to indicate that the default style must be 
     * used. 
     * 
     * @param DOMElement $odtElement 
     * @return string|null
     */
    protected function getStyleName( DOMElement $odtElement )
    {
        if ( !isset( $this->styleNameAttributeMap[$odtElement->namespaceURI] ) )
        {
            throw new RuntimeException( "Could not map style name attribute for namespace '{$odtElement->namespaceURI}'." );
        }
        $styleAttrDef = $this->styleNameAttributeMap[$odtElement->namespaceURI];

        $styleName = null;
        if ( $odtElement->hasAttributeNS( $styleAttrDef['namespace'], $styleAttrDef['attribute'] ) )
        {
            $styleName = $odtElement->getAttributeNS(
                $styleAttrDef['namespace'],
                $styleAttrDef['attribute']
            );
        }
        return $styleName;
    }

    /**
     * Returns the list style name of the given $odtElement.
     *
     * Recursively searches for the style name of the given list element. The 
     * style name is only present at the top most list (for nested lists).
     * 
     * @param DOMElement $odtElement 
     * @return string
     */
    protected function getListStyleName( DOMElement $odtElement )
    {
        if ( $odtElement->namespaceURI === ezcDocumentOdt::NS_ODT_TEXT
             && $odtElement->localName === 'list'
             && $odtElement->hasAttributeNS( ezcDocumentOdt::NS_ODT_TEXT, 'style-name' )
           )
        {
            return $odtElement->getAttributeNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'style-name'
            );
        }

        if ( $odtElement->parentNode === null )
        {
            throw new RuntimeException( 'No list style name found.' );
        }
        return $this->getListStyleName( $odtElement->parentNode );
    }
}

?>

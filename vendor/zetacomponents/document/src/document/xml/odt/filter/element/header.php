<?php
/**
 * File containing the ezcDocumentOdtElementHeaderFilter class.
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
 * Filter for ODT <text:h/> elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtElementHeaderFilter extends ezcDocumentOdtElementBaseFilter
{
    /**
     * Filter a single element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        $currentLevel = $element->getAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'outline-level'
        );

        $parent = $element->parentNode;
        $siblings = $parent->childNodes;

        $section = new ezcDocumentPropertyContainerDomElement(
            'section',
            null,
            ezcDocumentOdt::NS_EZC
        );
        $parent->replaceChild( $section, $element );
        $section->setProperty( 'type', 'section' );
        $section->setProperty( 'level', $currentLevel );

        $section->appendChild( $element );
        $element->setProperty( 'type', 'title' );

        for ( $i = 0; $i < $siblings->length; ++$i )
        {
            if ( $siblings->item( $i )->isSameNode( $section ) )
            {
                break;
            }
        }
        ++$i;

        while ( ( $sibling = $siblings->item( $i ) ) !== null )
        {
            if ( $sibling->nodeType === XML_ELEMENT_NODE
                 && $sibling->namespaceURI === ezcDocumentOdt::NS_EZC
                 && $sibling->getProperty( 'level' ) <= $currentLevel
               ) 
            {
                // Reached next higher or same level section
                break;
            }

            $section->appendChild( $sibling->cloneNode( true ) );
            $parent->removeChild( $sibling );
        }
    }

    /**
     * Check if filter handles the current element.
     *
     * Returns a boolean value, indicating weather this filter can handle
     * the current element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function handles( DOMElement $element )
    {
        return ( $element->namespaceURI === ezcDocumentOdt::NS_ODT_TEXT
            && $element->localName === 'h' );
    }
}

?>

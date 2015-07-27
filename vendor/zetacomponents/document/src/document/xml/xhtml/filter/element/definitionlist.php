<?php
/**
 * File containing the ezcDocumentXhtmlDefinitionListElementFilter class
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
 * Filter for XHtml definition lists
 *
 * Definition lists in XHtml are a specilized markup for terms and their
 * descriptions / definitions. In Docbook a term an its definitions are
 * surrounded by an additional element, which is added by this filter.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlDefinitionListElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        // We need to create invalid markup here, as there is no surrounding
        // element allowed for groups of dt and dd elements.
        $entry = new ezcDocumentPropertyContainerDomElement( 'div' );

        $term   = $element->cloneNode( true );
        $parent = $element->parentNode;

        // Replace header with new section node
        $parent->replaceChild( $entry, $element );
        $entry->setProperty( 'type', 'varlistentry' );
        $entry->appendChild( $term );

        // Skip all preceeding child elements, until we reach the current node.
        $children = $parent->childNodes;
        $childCount = $children->length;
        for ( $i = 0; $i < $childCount; ++$i )
        {
            if ( $entry->isSameNode( $children->item( $i ) ) )
            {
                break;
            }
        }
        ++$i;

        while ( ( $node = $children->item( $i ) ) !== null )
        {
            if ( ( $node->nodeType === XML_ELEMENT_NODE ) &&
                 ( ( $node->tagName === 'dt' ) ||
                   ( $node->tagName === 'dd' ) ) )
            {
                $new = $node->cloneNode( true );
                $entry->appendChild( $new );
                $parent->removeChild( $node );
            }
            else
            {
                ++$i;
            }
        }

    }

    /**
     * Check if filter handles the current element
     *
     * Returns a boolean value, indicating weather this filter can handle
     * the current element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function handles( DOMElement $element )
    {
        return ( $element->tagName === 'dt' );
    }
}

?>

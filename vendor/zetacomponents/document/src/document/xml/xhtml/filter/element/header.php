<?php
/**
 * File containing the ezcDocumentXhtmlHeaderElementFilter class
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
 * Filter for XHtml header elements, including grouping all following siblings
 * on the same header level in a section.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlHeaderElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        // Create new parent node if we found a header and aggregate everything
        // below the actual header into this node.
        $section = new ezcDocumentPropertyContainerDomElement( 'div' );

        $parent = $element->parentNode;

        // Replace header with new section node
        $parent->replaceChild( $section, $element );
        $section->setProperty( 'type', 'section' );
        $section->setProperty( 'level', $level = $this->getHeaderLevel( $element ) );

        $section->appendChild( $element );
        $element->setProperty( 'type', 'title' );

        // Skip all preceeding child elements, until we reach the current node.
        $children = $parent->childNodes;
        $childCount = $children->length;
        for ( $i = 0; $i < $childCount; ++$i )
        {
            if ( $section->isSameNode( $children->item( $i ) ) )
            {
                break;
            }
        }
        ++$i;

        while ( ( $node = $children->item( $i ) ) !== null )
        {
            if ( ( $node->nodeType === XML_ELEMENT_NODE ) &&
                 ( $node->tagName === 'div' ) &&
                 ( $node->getProperty( 'type' ) === 'section' ) &&
                 ( $node->getProperty( 'level' ) <= $level ) )
            {
                break;
            }
            else
            {
                $new = $node->cloneNode( true );
                $section->appendChild( $new );
                $parent->removeChild( $node );
            }
        }
    }

    /**
     * Get header level
     *
     * Get the header level of a HTML heading. Additionally to the default
     * levels h1-6 we repect a level specified in the class attribute, which is
     * for example used by the RST to XHtml conversion to specify header levels
     * higher then 6.
     *
     * @param DOMElement $element
     * @return int
     */
    protected function getHeaderLevel( DOMElement $element )
    {
        $headerLevel = (int) $element->tagName[1];
        if ( $headerLevel === 6 )
        {
            if ( $element->hasAttribute( 'class' ) &&
                 preg_match( '((?:\s|^)h(?P<level>\d+)(?:\s|$))', $element->getAttribute( 'class' ), $match ) )
            {
                $headerLevel = (int) $match['level'];
            }
        }

        return $headerLevel;
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
        return (bool) preg_match( '(^[hH][1-6]$)', $element->tagName );
    }
}

?>

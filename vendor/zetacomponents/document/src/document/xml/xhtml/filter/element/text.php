<?php
/**
 * File containing the ezcDocumentXhtmlTextToParagraphFilter class
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
 * Filter for abandoned text
 *
 * Converts text, which is not wrapped by any nodes, which may contain inline
 * markup, into paragraphs containing the text.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlTextToParagraphFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        $aggregated = array();
        $processed  = array();
        for ( $i = ( $element->childNodes->length - 1 ); $i >= 0; --$i )
        {
            // Get type of current row, or set row type to null, if it is no
            // table row.
            $child   = $element->childNodes->item( $i );
            $childNr = $i;

            // There are three different actions, which need to be performed in
            // this loop:
            //  - Aggregate text and inline nodes
            //  - Move text nodes to new paragraph nodes.
            if ( ( count( $aggregated ) ) &&
                   ( ( $i <= 0 ) ||
                     ( !$this->isInlineElement( $child ) ) ) )
            {
                // We only create a new paragraph node around the aggregated
                // elements, if they contain at least one text node.
                $wrap = false;
                foreach ( $aggregated as $node )
                {
                    if ( $node->nodeType === XML_TEXT_NODE )
                    {
                        $wrap = true;
                        break;
                    }
                }

                if ( $wrap )
                {
                    // Move nodes to new subnode
                    $lastNode = end( $aggregated );
                    $newNode = new ezcDocumentPropertyContainerDomElement( 'p' );
                    $child->parentNode->insertBefore( $newNode, $lastNode );

                    // Append all aggregated nodes
                    $aggregated = array_reverse( $aggregated );
                    foreach ( $aggregated as $node )
                    {
                        $cloned = $node->cloneNode( true );
                        $newNode->appendChild( $cloned );
                        $child->parentNode->removeChild( $node );
                    }

                    // Clean up
                    $aggregated = array();

                    // Maybe we need to handle the current element again.
                    ++$i;
                }
            }

            if ( ( $child->nodeType !== XML_ELEMENT_NODE ) &&
                 ( $child->nodeType !== XML_TEXT_NODE ) &&
                 ( $child->nodeType !== XML_COMMENT_NODE ) )
            {
                $child->parentNode->removeChild( $child );
                continue;
            }
            elseif ( $this->isInlineElement( $child ) &&
                     ( !isset( $processed[$childNr] ) ) )
            {
                // Aggregate nodes
                $aggregated[]        = $child;
                $processed[$childNr] = true;
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
        if ( in_array( $element->tagName, array( 
                'div',
                'del',
                'ins',
             ) ) &&
             ( $element->parentNode instanceof DOMElement ) )
        {
            return $this->handles( $element->parentNode );
        }

        return in_array( $element->tagName, array(
            'body',
            'dd',
            'fieldset',
            'form',
            'li',
            'menu',
            'th',
            'td',
        ) );
    }
}

?>

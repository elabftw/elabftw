<?php
/**
 * File containing the ezcDocumentXhtmlTableElementFilter class
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
 * Filter for XHtml table elements.
 *
 * Tables, where the rows are nor structured into a tbody and thead are
 * restructured into those by this filter.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlTableElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        $type       = false;
        $aggregated = array();
        $processed  = array();
        for ( $i = ( $element->childNodes->length - 1 ); $i >= -1; --$i )
        {
            // Get type of current row, or set row type to null, if it is no
            // table row.
            $child   = $element->childNodes->item( $i );
            $childNr = $i;
            if ( $child &&
                 ( $child->nodeType === XML_ELEMENT_NODE ) &&
                 ( $child->tagName === 'tr' ) )
            {
                $rowType = $this->getType( $child );
            }
            else
            {
                $rowType = null;
            }

            // There are three different actions, which need to be performed in
            // this loop:
            //  - Skip irrelevant nodes (whitespaces)
            //  - Aggregate tr nodes
            //  - Move tr nodes to new tbody / thead nodes, depending on their
            //    type, when the row type changes, we reached the last row, or
            //    their is some tbody / thead node found.
            if ( ( count( $aggregated ) ) &&
                   ( ( $i < 0 ) ||
                     ( ( $rowType !== null ) &&
                       ( $rowType !== $type ) ) ) )
            {
                // Move nodes to new subnode
                $lastNode = end( $aggregated );
                $parent   = $lastNode->parentNode;
                $newNode  = new ezcDocumentPropertyContainerDomElement( $type );
                $parent->insertBefore( $newNode, $lastNode );
                $newNode->setProperty( 'type', $type );

                // Append all aggregated nodes
                $aggregated = array_reverse( $aggregated );
                foreach ( $aggregated as $node )
                {
                    $cloned = $node->cloneNode( true );
                    $newNode->appendChild( $cloned );
                    $parent->removeChild( $node );
                }

                // Clean up
                $aggregated = array();
                $type = false;

                // Maybe we need to handle the current element again.
                ++$i;
            }

            if ( $child &&
                 ( $child->nodeType !== XML_ELEMENT_NODE ) )
            {
                $child->parentNode->removeChild( $child );
                continue;
            }
            elseif ( ( $rowType !== null ) &&
                     ( !isset( $processed[$childNr] ) ) )
            {
                // Aggregate nodes
                $aggregated[]        = $child;
                $processed[$childNr] = true;
                $type                = $rowType;
            }
        }
    }

    /**
     * Estimate type of a row
     *
     * Estimate, if a row in a table is a header or a footer row. This
     * estiamtion checks if there are more th elements, the td elements and
     * returns either 'thead' or 'tbody' as the row type on base of that.
     *
     * @param DOMElement $element
     * @return string
     */
    protected function getType( DOMElement $element )
    {
        $thCount = $element->getElementsByTagName( 'th' )->length;
        $tdCount = $element->getElementsByTagName( 'td' )->length;

        return ( $thCount < $tdCount ) ? 'tbody' : 'thead';
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
        return ( $element->tagName === 'table' );
    }
}

?>

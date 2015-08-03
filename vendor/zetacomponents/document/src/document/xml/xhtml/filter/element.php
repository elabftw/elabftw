<?php
/**
 * File containing the ezcDocumentXhtmlElementFilter class
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
 * Filter, which assigns semantic information just on the base of XHtml element
 * semantics to the tree.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlElementFilter extends ezcDocumentXhtmlBaseFilter
{
    /**
     * List of element filter objects.
     *
     * @var array
     */
    protected $elementFilter = array();

    /**
     * Constructor
     *
     * Create initial filter array.
     *
     * @return void
     */
    public function __construct()
    {
        // Special handling required
        //  - object
        $this->elementFilter = array(
            // Basic mapping filter
            new ezcDocumentXhtmlElementMappingFilter(),

            // Special filters for more complex elements
            new ezcDocumentXhtmlHeaderElementFilter(),
            new ezcDocumentXhtmlLinkElementFilter(),
            new ezcDocumentXhtmlLiteralElementFilter(),
            new ezcDocumentXhtmlStrongElementFilter(),
            new ezcDocumentXhtmlBlockquoteElementFilter(),
            new ezcDocumentXhtmlEnumeratedElementFilter(),
            new ezcDocumentXhtmlImageElementFilter(),
            new ezcDocumentXhtmlFootnoteElementFilter(),
            new ezcDocumentXhtmlDefinitionListElementFilter(),
            new ezcDocumentXhtmlLineBlockElementFilter(),
            new ezcDocumentXhtmlTableElementFilter(),
            new ezcDocumentXhtmlTableCellElementFilter(),
            new ezcDocumentXhtmlSpecialParagraphElementFilter(),
            new ezcDocumentXhtmlTextToParagraphFilter(),

            // Since paragraphs are often annotated differently, or texts may
            // be removed in other filters, it is best to keep this filter near
            // the end.
            new ezcDocumentXhtmlParagraphElementFilter(),
        );
    }

    /**
     * Add additional element filter
     *
     * @param ezcDocumentXhtmlElementBaseFilter $filter
     * @return void
     */
    public function addFilter( ezcDocumentXhtmlElementBaseFilter $filter )
    {
        $this->elementFilter[] = $filter;
    }

    /**
     * Filter XHtml document
     *
     * Filter for the document, which may modify / restructure a document and
     * assign semantic information bits to the elements in the tree.
     *
     * @param DOMDocument $document
     * @return DOMDocument
     */
    public function filter( DOMDocument $document )
    {
        $xpath = new DOMXPath( $document );
        $root = $xpath->query( '/*[local-name() = "html"]' )->item( 0 );
        $this->filterNode( $root );
    }

    /**
     * Filter node
     *
     * Depending on the element name, it parents and maybe element attributes
     * semantic information is assigned to nodes.
     *
     * @param DOMElement $element
     * @return void
     */
    protected function filterNode( DOMElement $element )
    {
        // Check all available filters, if they want to handle the current
        // element and let them handle the element in this case.
        foreach ( $this->elementFilter as $filter )
        {
            if ( $filter->handles( $element ) )
            {
                $filter->filterElement( $element );
            }
        }

        // Recurse into child elements
        //
        // We do the recursion in the reverse order of the elements, so that
        // restructuring of the tree does not affect the nodes, which are yet
        // up for filtering - this might end in errors about missing nodes
        // otherwise.
        for ( $i = ( $element->childNodes->length - 1 ); $i >= 0; --$i )
        {
            $child = $element->childNodes->item( $i );
            if ( $child->nodeType === XML_ELEMENT_NODE )
            {
                $this->filterNode( $child );
            }
        }
    }
}

?>

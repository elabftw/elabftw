<?php
/**
 * File containing the ezcDocumentXhtmlMetadataFilter class
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
class ezcDocumentXhtmlMetadataFilter extends ezcDocumentXhtmlBaseFilter
{
    /**
     * Metadata name mapping
     *
     * @var array
     */
    protected $mapping = array(
        // Common meta field names
        'description' => 'abstract',
        'version'     => 'releaseinfo',
        'date'        => 'date',
        'author'      => 'author',
        'authors'     => 'author',

        // Meta element dublin core extensions
        'dc.title'       => 'title',
        'dc.creator'     => 'author',
        // 'dc.subject' => '',
        'dc.description' => 'abstract',
        'dc.publisher'   => 'publisher',
        'dc.contributor' => 'contrib',
        'dc.date'        => 'date',
        // 'dc.type' => '',
        // 'dc.format' => '',
        // 'dc.identifier' => '',
        // 'dc.source' => '',
        // 'dc.relation' => '',
        // 'dc.coverage' => '',
        'dc.rights'      => 'copyright',
    );

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

        // Remove document title, as it is not
        $metadata = $xpath->query( '/*[local-name() = "html"]/*[local-name() = "head"]/*[local-name() = "meta"]' );
        foreach ( $metadata as $node )
        {
            $this->filterMetaData( $node );
        }
    }

    /**
     * Filter meta data
     *
     * Filter meta elements in HTML header for relevant metadata.
     *
     * @param DOMElement $element
     * @return void
     */
    protected function filterMetaData( DOMElement $element )
    {
        if ( $element->hasAttribute( 'name' ) &&
             $element->hasAttribute( 'content' ) &&
             ( $name = strtolower( $element->getAttribute( 'name' ) ) ) &&
             ( isset( $this->mapping[$name] ) ) )
        {
            // Set type of element
            $element->setProperty( 'type', $this->mapping[$name] );

            // Apply special parsing and conversion to some of the given
            // elements
            switch ( $this->mapping[$name] )
            {
                case 'abstract':
                    $textNode = $element->ownerDocument->createElement( 'span' );
                    $textNode->setProperty( 'type', 'para' );
                    $element->appendChild( $textNode );
                    break;

                default:
                    $textNode = $element;
            }

            // Set conents as child text node.
            $text = new DOMText( $element->getAttribute( 'content' ) );
            $textNode->appendChild( $text );
        }
    }
}

?>

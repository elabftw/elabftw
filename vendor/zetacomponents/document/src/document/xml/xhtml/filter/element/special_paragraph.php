<?php
/**
 * File containing the ezcDocumentXhtmlSpecialParagraphElementFilter class
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
 * Filter for XHtml table cells.
 *
 * Tables, where the rows are nor structured into a tbody and thead are
 * restructured into those by this filter.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlSpecialParagraphElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Mapping of special paragraph types to their docbook equivalents
     *
     * @var array
     */
    protected $typeMapping = array(
        'note'      => 'note',
        'notice'    => 'tip',
        'warning'   => 'warning',
        'attention' => 'important',
        'danger'    => 'caution',
    );

    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        foreach ( $this->typeMapping as $class => $type )
        {
            if ( $this->hasClass( $element, $class ) )
            {
                $element->setProperty( 'type', $type );

                // Create a paragraph node wrapping the contents
                $para = $element->ownerDocument->createElement( 'span' );
                $para->setProperty( 'type', 'para' );

                while ( $element->firstChild )
                {
                    $cloned = $element->firstChild->cloneNode( true );
                    $para->appendChild( $cloned );
                    $element->removeChild( $element->firstChild );
                }

                $element->appendChild( $para );
                break;
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
        return ( $element->tagName === 'p' ) &&
               ( $element->hasAttribute( 'class' ) );
    }
}

?>

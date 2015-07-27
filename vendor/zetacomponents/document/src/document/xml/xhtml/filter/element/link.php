<?php
/**
 * File containing the ezcDocumentXhtmlLinkElementFilter class
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
 * Filter for XHtml links.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlLinkElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        if ( $element->hasAttribute( 'name' ) )
        {
            $span = new ezcDocumentPropertyContainerDomElement( 'span' );
            $element->parentNode->insertBefore( $span, $element );

            // The a element is an anchor
            $span->setProperty( 'type', 'anchor' );
            $span->setProperty( 'attributes', array(
                'ID' => $element->getAttribute( 'name' ),
            ) );
        }
        elseif ( $element->hasAttribute( 'href' ) &&
                 $element->getAttribute( 'href' ) )
        {
            // The element is a reference, but still may be internal or
            // external
            $target = $element->getAttribute( 'href' );
            if ( $target[0] === '#' )
            {
                // Internal target
                $element->setProperty( 'type', 'link' );
                $element->setProperty( 'attributes', array(
                    'linked' => substr( $target, 1 ),
                ) );
            }
            else
            {
                // External target
                $element->setProperty( 'type', 'ulink' );
                $element->setProperty( 'attributes', array(
                    'url' => $target,
                ) );
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
        // @todo: Add support for xlink
        return ( $element->tagName === 'a' ) &&
            $this->isInline( $element );
    }
}

?>

<?php
/**
 * File containing the ezcDocumentXhtmlLineBlockElementFilter class
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
 * Filter for XHtml line blocks
 *
 * There is no semantic markup for something like line blocks in HTML. Line
 * blocks are basically text with manual breaks at the end of each line (like
 * in poems). In HTML this is often indicated by a paragraph with several br
 * tags inside.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlLineBlockElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        if ( $element->tagName === 'p' )
        {
            $element->setProperty( 'type', 'literallayout' );
            $element->setProperty( 'attributes', array(
                'class' => 'normal',
            ) );
        }
        else
        {
            $element->appendChild( new DOMText( "\n" ) );
            $element->setProperty( 'whitespace', 'significant' );
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
        return ( $element->tagName === 'br' ) ||
               ( ( $element->tagName === 'p' ) &&
                 ( ( $this->hasClass( $element, 'lineblock' ) ||
                   ( $element->getElementsByTagName( 'br' )->length ) ) ) );
    }
}

?>

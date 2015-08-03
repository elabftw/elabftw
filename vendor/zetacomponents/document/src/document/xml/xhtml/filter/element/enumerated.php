<?php
/**
 * File containing the ezcDocumentXhtmlEnumeratedElementFilter class
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
 * Filter for XHtml enumerated lists.
 *
 * Enumerated lists may have additional information about the list type
 * they are numbered with (alpha, roman, ..), which is kept by this method.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlEnumeratedElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        $element->setProperty( 'type', 'orderedlist' );

        $types = array(
            'a' => 'loweralpha',
            'A' => 'upperalpha',
            'i' => 'lowerroman',
            'I' => 'upperroman',
        );
        if ( $element->hasAttribute( 'type' ) &&
             isset( $types[$type = $element->getAttribute( 'type' )] ) )
        {
            $element->setProperty( 'attributes', array(
                'numeration' => $types[$type],
            ) );
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
        return  ( $element->tagName === 'ol' );
    }
}

?>

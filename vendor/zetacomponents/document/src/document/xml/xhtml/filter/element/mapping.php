<?php
/**
 * File containing the ezcDocumentXhtmlElementMappingFilter class
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
 * Filter for XHtml elements, which just do require some plain mapping to
 * semantic docbook elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlElementMappingFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Mapping of XHtml elements to their semantic meanings.
     *
     * @var array
     */
    protected $nameMapping = array(
        'abbr'       => 'abbrev',
        'acronym'    => 'acronym',
        'big'        => 'emphasis',
        'blockquote' => 'blockquote',
        'dl'         => 'variablelist',
        'dt'         => 'term',
        'dd'         => 'listitem',
        'em'         => 'emphasis',
        'head'       => 'sectioninfo',
        'hr'         => 'beginpage',
        'html'       => 'section',
        'i'          => 'emphasis',
        'li'         => 'listitem',
        'q'          => 'blockquote',
        'title'      => 'title',
        'tt'         => 'literal',
        'table'      => 'table',
        'td'         => 'entry',
        'th'         => 'entry',
        'tr'         => 'row',
        'tbody'      => 'tbody',
        'thead'      => 'thead',
        'u'          => 'emphasis',
        'ul'         => 'itemizedlist',
    );

    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        if ( $this->isInlineElement( $element ) &&
             !$this->isInline( $element ) )
        {
            return;
        }

        $element->setProperty(
            'type',
            $this->nameMapping[$element->tagName]
        );
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
        return isset( $this->nameMapping[$element->tagName] );
    }
}

?>

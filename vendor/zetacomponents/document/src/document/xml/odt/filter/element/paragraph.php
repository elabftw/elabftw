<?php
/**
 * File containing the ezcDocumentOdtElementParagraphFilter class.
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
 * Filter for ODT <text:p> elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtElementParagraphFilter extends ezcDocumentOdtElementBaseFilter
{
    /**
     * Filter a single element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        if ( $this->hasSignificantWhitespace( $element ) )
        {
            $element->setProperty( 'type', 'literallayout' );
        }
        else 
        {
            $element->setProperty( 'type', 'para' );
        }
    }

    /**
     * Returns if significant whitespaces occur in the paragraph.
     *
     * This method checks if the paragraph $element contains significant
     * whitespaces in form of <text:s/> or <text:tab/> elements.
     * 
     * @param DOMElement $element 
     * @return bool
     */
    protected function hasSignificantWhitespace( DOMElement $element ) 
    {
        $xpath = new DOMXpath( $element->ownerDocument );
        $xpath->registerNamespace( 'text', ezcDocumentOdt::NS_ODT_TEXT );
        $whitespaces = $xpath->evaluate( './/text:s|.//text:tab|.//text:line-break', $element );

        return ( $whitespaces instanceof DOMNodeList && $whitespaces->length > 0 );
    }

    /**
     * Check if filter handles the current element.
     *
     * Returns a boolean value, indicating weather this filter can handle
     * the current element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function handles( DOMElement $element )
    {
        return ( $element->namespaceURI === ezcDocumentOdt::NS_ODT_TEXT
            && $element->localName === 'p' );
    }
}

?>

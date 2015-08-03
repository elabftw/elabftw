<?php
/**
 * File containing the ezcDocumentOdtElementImageFilter class.
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
 * Filter for ODT <draw:image> elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtElementImageFilter extends ezcDocumentOdtElementBaseFilter
{
    /**
     * Filter a single element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        $frame = $element->parentNode;

        $element->setProperty( 'type', 'imageobject' );

        $imageData = new ezcDocumentPropertyContainerDomElement(
            'imagedata',
            null,
            ezcDocumentOdt::NS_EZC
        );
        $this->insertImageData( $element, $imageData );
        $imageData->setProperty( 'type', 'imagedata' );
        
        $attributes = array(
            'fileref' => $element->getAttributeNS(
                ezcDocumentOdt::NS_XLINK,
                'href'
            )
        );
        if ( $frame->hasAttributeNS( ezcDocumentOdt::NS_ODT_SVG, 'width' ) )
        {
            $attributes['width'] = $frame->getAttributeNS( ezcDocumentOdt::NS_ODT_SVG, 'width' );
        }
        if ( $frame->hasAttributeNS( ezcDocumentOdt::NS_ODT_SVG, 'height' ) )
        {
            $attributes['depth'] = $frame->getAttributeNS( ezcDocumentOdt::NS_ODT_SVG, 'height' );
        }

        $imageData->setProperty(
            'attributes',
            $attributes
        );
    }

    /**
     * Inserts $imageData as a child into $imageObject.
     *
     * Detects if $imageObject contains <office:binary-data/>. If this is the case, 
     * this element is replaced with the given $imageData. Otherwise, 
     * $imageData is added as a new child.
     * 
     * @param DOMElement $imageObject 
     * @param DOMElement $imageData 
     */
    protected function insertImageData( $imageObject, $imageData )
    {
        $binaryDataElems = $imageObject->getElementsByTagNameNS(
            ezcDocumentOdt::NS_ODT_OFFICE,
            'binary-data'
        );
        if ( $binaryDataElems->length === 1 )
        {
            $imageObject->replaceChild( $imageData, $binaryDataElems->item( 0 ) );
        }
        else
        {
            $imageObject->appendChild( $imageData );
        }
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
        return ( $element->namespaceURI === ezcDocumentOdt::NS_ODT_DRAWING
            && $element->localName === 'image' );
    }
}

?>

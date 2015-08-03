<?php
/**
 * File containing the ezcDocumentOdtPcssParagraphStylePreprocessor class.
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
 * @access private
 * @package Document
 * @version //autogen//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Paragraph style pre-processor.
 *
 * Pre-processes paragraph styles. If there is a <beginpage/> element right 
 * before the processed paragraph the custom "break-before" PCSS property is 
 * set to "page", which will result in a corresponding ODT style attribute.
 *
 * @access private
 * @package Document
 * @version //autogen//
 */
class ezcDocumentOdtPcssParagraphStylePreprocessor
{
    /**
     * Pre-process styles and return them.
     *
     * Performs some detection of list styles in the $docBookElement and its 
     * document and sets according PCSS properties in $styles.
     *
     * @param ezcDocumentOdtStyleInformation $styleInfo
     * @param DOMElement $docBookElement
     * @param DOMElement $odtElement 
     * @param array $styles 
     * @return array
     */
    public function process( ezcDocumentOdtStyleInformation $styleInfo, DOMElement $docBookElement, DOMElement $odtElement, array $styles )
    {
        if ( ( $odtElement->localName === 'h' || $odtElement->localName === 'p' )
             && $this->isOnNewPage( $docBookElement )
           )
        {
            $styles['break-before'] = new ezcDocumentPcssStyleStringValue( 'page' );
        }
        return $styles;
    }

    /**
     * Returns if the given $docBookElement is to be rendered on a new page.
     *
     * @param DOMElement $docBookElement
     * @return bool
     */
    protected function isOnNewPage( DOMElement $docBookElement )
    {
        while ( $docBookElement->previousSibling !== null )
        {
            $docBookElement = $docBookElement->previousSibling;
            if ( $docBookElement->nodeType === XML_ELEMENT_NODE )
            {
                if ( $docBookElement->localName === 'beginpage' )
                {
                    return true;
                }
                break;
            }
        }
        return false;
    }
}

?>

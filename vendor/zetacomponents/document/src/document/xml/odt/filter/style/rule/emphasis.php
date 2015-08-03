<?php
/**
 * File containing the ezcDocumentOdtEmphasisStyleFilterRule class.
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
 * Style filter rule to detect <emphasis/> elements.
 *
 * This style filter rule checks <text:span/> elements in ODT for bold 
 * font-weight. Such elements are considered to be translated to <emphasis/> 
 * elements in DocBook.
 *
 * @package Document
 * @version //autogen//
 * @access private
 * @todo Emphasis can also be indicated by other styles like red color or 
 *       similar. In addition, emphasis should be detected relatively to the 
 *       surrounding style. Some kind of points-threshold-based system would 
 *       be nice.
 */
class ezcDocumentOdtEmphasisStyleFilterRule implements ezcDocumentOdtStyleFilterRule
{
    /**
     * Returns if the given $odtElement is handled by the rule.
     * 
     * @param DOMElement $odtElement 
     * @return bool
     */
    public function handles( DOMElement $odtElement )
    {
        return ( $odtElement->localName === 'span' );
    }

    /**
     * Detects emphasis elements by their style.
     *
     * This method checks the style of the given $odtElement for bold 
     * font-weight ("bold" or value >= 700). If this is detected, the type of 
     * the element is set to be <emphasis/>.
     * 
     * @param DOMElement $odtElement 
     * @param ezcDocumentOdtStyleInferencer $styleInferencer
     */
    public function filter( DOMElement $odtElement, ezcDocumentOdtStyleInferencer $styleInferencer )
    {
        $style = $styleInferencer->getStyle( $odtElement );
        $textProps = $style->formattingProperties->getProperties(
            ezcDocumentOdtFormattingProperties::PROPERTIES_TEXT
        );

        if ( isset( $textProps['font-weight'] ) && ( $textProps['font-weight'] === 'bold' || $textProps['font-weight'] >= 700 ) )
        {
            $odtElement->setProperty(
                'type',
                'emphasis'
            );
        }
    }
}

?>

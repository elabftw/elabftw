<?php
/**
 * File containing the ezcDocumentOdtPcssTextDecorationConverter class.
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
 * Style converter for text-decoration style properties.
 *
 * @package Document
 * @access private
 * @version //autogen//
 * @todo ODT supports much more fine-graned text-decoration properties than 
 *       PCSS currently supports. Should try to support more ODT features in 
 *       latter versions.
 */
class ezcDocumentOdtPcssTextDecorationConverter implements ezcDocumentOdtPcssConverter
{
    /**
     * Converts the 'text-decoration' CSS style.
     *
     * This method receives a $targetProperty DOMElement and converts the given 
     * style with $styleName and $styleValue to attributes on this 
     * $targetProperty.
     * 
     * @param DOMElement $targetProperty 
     * @param string $styleName 
     * @param ezcDocumentPcssStyleValue $styleValue 
     */
    public function convert( DOMElement $targetProperty, $styleName, ezcDocumentPcssStyleValue $styleValue )
    {
        foreach ( $styleValue->value as $listElement )
        {
            switch ( $listElement )
            {
                case 'line-through':
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-line-through-type',
                        'single'
                    );
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-line-through-style',
                        'solid'
                    );
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-line-through-width',
                        'auto'
                    );
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-line-through-color',
                        'font-color'
                    );
                    break;
                case 'underline':
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-underline-type',
                        'single'
                    );
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-underline-style',
                        'solid'
                    );
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-underline-width',
                        'auto'
                    );
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-underline-color',
                        'font-color'
                    );
                    break;
                case 'overline':
                    break;
                case 'blink':
                    $targetProperty->setAttributeNS(
                        ezcDocumentOdt::NS_ODT_STYLE,
                        'style:text-blinking',
                        'true'
                    );
                    break;
            }
        }
    }
}

?>

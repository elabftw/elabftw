<?php
/**
 * File containing the ezcDocumentOdtPcssBorderConverter class.
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
 * Style converter for border style properties.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentOdtPcssBorderConverter implements ezcDocumentOdtPcssConverter
{
    /**
     * Converts CSS 'border' style.
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
        foreach ( $styleValue->value as $type => $borderValues )
        {
            $targetProperty->setAttributeNS(
                ezcDocumentOdt::NS_ODT_FO,
                "fo:border-{$type}",
                sprintf(
                    "%smm %s %s",
                    $borderValues['width'],
                    $borderValues['style'],
                    ezcDocumentOdtPcssConverterTools::serializeColor( $borderValues['color'] )
                )
            );
        }
    }
}

?>

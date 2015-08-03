<?php
/**
 * File containing the ezcDocumentOdtPcssPreprocessor interface.
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
 * PCSS style preprocessor interface.
 *
 * Style pre-processors hook into the {@link ezcDocumentOdtStyler} right after 
 * the styles for an element have been determined and right before the 
 * corresponding style information is generated in the ODT document and applied 
 * to the ODT element. Pre-processors may generate styling information which is 
 * not provided by PCSS or stored in other ways as well as unify styles and 
 * process new styles from existing ones.
 *
 * @access private
 * @package Document
 * @version //autogen//
 */
interface ezcDocumentOdtPcssPreprocessor
{
    /**
     * Pre-process styles and return them.
     *
     * This method may pre-process the $styles generated from the 
     * $docBookElement for the given $odtElement. The processing may include 
     * creation of new style attributes and manipulation of style attributes.  
     * Removal of style attributes is discouraged!
     * 
     * In addition, a pre-processor may utilize the DOMElements from the $styleInfo struct 
     * to extract additional information needed or to perform style related DOM 
     * manipultations.
     *
     * @param ezcDocumentOdtStyleInformation $styleInfo
     * @param DOMElement $docBookElement
     * @param DOMElement $odtElement 
     * @param array $styles 
     * @return array
     */
    function process( ezcDocumentOdtStyleInformation $styleInfo, DOMElement $docBookElement, DOMElement $odtElement, array $styles );
}

?>

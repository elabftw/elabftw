<?php
/**
 * File containing the ezcDocumentOdtStyleInformation struct class.
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
 * Struct class to cover style elements from an ODT document.
 *
 * @package Document
 * @access private
 * @version //autogen//
 */
class ezcDocumentOdtStyleInformation extends ezcBaseStruct
{
    /**
     * Style section of the target ODT.
     * 
     * @var DOMElement
     */
    public $styleSection;

    /**
     * Automatic style section of the target ODT. 
     * 
     * @var mixed
     */
    public $automaticStyleSection;

    /**
     * Font face declaration section of the target ODT. 
     * 
     * @var DOMElement
     */
    public $fontFaceDecls;

    /**
     * Creates a new ODT style information struct.
     *
     * The $styleSection and $fontFaceDecls must be from the target ODT 
     * DOMDocument.
     * 
     * @param DOMElement $styleSection 
     * @param DOMElement $automaticStyleSection 
     * @param DOMElement $fontFaceDecls 
     */
    public function __construct( DOMElement $styleSection, DOMElement $automaticStyleSection, DOMElement $fontFaceDecls )
    {
        $this->styleSection          = $styleSection;
        $this->automaticStyleSection = $automaticStyleSection;
        $this->fontFaceDecls         = $fontFaceDecls;
    }
}

?>

<?php
/**
 * File containing the ezcDocumentOdtPcssFontStylePreprocessor class.
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
 * Font style pre-processor.
 *
 * Extracts the font-family PCSS property and registers the font in the 
 * font-face-decls section of the ODT. Generates the custom font-name PCSS 
 * property to be set in the actual style section.
 *
 * @access private
 * @package Document
 * @version //autogen//
 */
class ezcDocumentOdtPcssFontStylePreprocessor
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
        if ( isset( $styles['font-family'] ) )
        {
            $styles['font-name'] = new ezcDocumentPcssStyleStringValue(
                $this->registerFont(
                    $styleInfo->fontFaceDecls,
                    $styles['font-family']
                )
            );
        }
        return $styles;
    }
    
    /**
     * Checks if the font is already registered or creates a new declaration.
     *
     * Checks if the given $fontFamily is already registered in $fontFaceDecls. 
     * If it is, it's generic font-name is returned. Otherwise a new font face 
     * declaration is created and the chosen font-name is returned.
     * 
     * @param DOMElement $fontFaceDecls 
     * @param string $fontFamily 
     * @return string
     */
    protected function registerFont( DOMElement $fontFaceDecls, $fontFamily )
    {
        $existingFonts = $fontFaceDecls->getElementsByTagnameNS(
            ezcDocumentOdt::NS_ODT_STYLE,
            'font-face'
        );
        foreach ( $existingFonts as $fontDecl )
        {
            if ( $fontDecl->getAttributeNS( ezcDocumentOdt::NS_ODT_STYLE, 'name' ) == $fontFamily )
            {
                return $fontDecl->getAttributeNS( ezcDocumentOdt::NS_ODT_STYLE, 'name' );
            }
        }
        return $this->createNewFontDecl( $fontFaceDecls, $fontFamily );
    }

    /**
     * Creates a new font declaration and returns the font-name.
     * 
     * @param DOMElement $fontFaceDecls 
     * @param string $fontFamily 
     * @return string
     */
    protected function createNewFontDecl( DOMElement $fontFaceDecls, $fontFamily )
    {
        $fontDecl = $fontFaceDecls->appendChild(
            $fontFaceDecls->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_STYLE,
                'style:font-face'
            )
        );
        $fontDecl->setAttributeNS(
            ezcDocumentOdt::NS_ODT_STYLE,
            'style:name',
            $fontFamily
        );
        $fontDecl->setAttributeNS(
            ezcDocumentOdt::NS_ODT_SVG,
            'svg:font-family',
            ( strpos( $fontFamily, ' ' ) !== false
                ? "'{$fontFamily}'"
                : $fontFamily
            )
        );
        // @todo: Should be roman, swiss, modern, decorative, script or system. 
        // Can we determine this somehow?
        $fontDecl->setAttributeNS(
            ezcDocumentOdt::NS_ODT_STYLE,
            'style:font-family-generic',
            'system'
        );
        // @todo: Configurable?
        $fontDecl->setAttributeNS(
            ezcDocumentOdt::NS_ODT_STYLE,
            'style:font-pitch',
            'variable'
        );
        return $fontFamily;
    }

}

?>

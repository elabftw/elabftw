<?php
/**
 * File containing the ezcDocumentOdtStyleExtractor class.
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
 * Extracts style information from an ODT document.
 *
 * An instance of this class is used to extract styles from an ODT 
 * DOMDocument.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentOdtStyleExtractor
{
    /**
     * The ODT document.
     * 
     * @var DOMDocument
     */
    protected $odt;

    /**
     * XPath object on the ODT document.
     * 
     * @var DOMXPath
     */
    protected $xpath;

    /**
     * Creates a new style extractor for the given $odt document.
     * 
     * @param DOMDocument $odt 
     */
    public function __construct( DOMDocument $odt )
    {
        $this->odt = $odt;

        $this->xpath = new DOMXpath( $odt );
        $this->xpath->registerNamespace( 'style', ezcDocumentOdt::NS_ODT_STYLE );
        $this->xpath->registerNamespace( 'text',  ezcDocumentOdt::NS_ODT_TEXT );
    }

    /**
     * Extract the style identified by $family and $name.
     *
     * Returns the DOMElement for the style identified by $family and $name. If 
     * $name is left out, the default style for $family will be extracted.
     * 
     * @param string $family 
     * @param string $name 
     * @return DOMElement
     */
    public function extractStyle( $family, $name = null )
    {
        $xpath = '//';
        if ( $name === null )
        {
            $xpath .= "style:default-style[@style:family='{$family}']";
        }
        else
        {
            $xpath .= "style:style[@style:family='{$family}' and @style:name='{$name}']";
        }
        $styles = $this->xpath->query( $xpath );

        if ( $styles->length !== 1 )
        {
            throw new RuntimeException( "Style of family '$family' with name '$name' not found." );
        }
        return $styles->item( 0 );
    }

    /**
     * Extracts the list style identified by $name.
     *
     * Returns the DOMElement for the list style identified by $name.
     *
     * @param string $name 
     * @return DOMElement
     * @todo Make $name optional and allow extraction of default list styles.
     */
    public function extractListStyle( $name )
    {
        $xpath = '//text:list-style[@style:name="' . $name . '"]';

        $styles = $this->xpath->query( $xpath );

        if ( $styles->length !== 1 )
        {
            throw new RuntimeException( "List style with name '$name' not found." );
        }
        return $styles->item( 0 );
    }
}

?>
